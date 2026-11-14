<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of IliasIdentitySwitch for BASE3 Framework.
 *
 * IliasIdentitySwitch manages temporary native ILIAS identities.
 * It provides authorized session switching and safe restoration.
 * Native ILIAS permissions continue to govern every operation.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0-or-later
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 ***********************************************************************/


namespace Base3\IliasIdentitySwitch\Service;

use Base3\CoreFoundation\Authorization\Api\IAuthorizationService;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationRequest;
use Base3\CoreFoundation\Logger\Api\ILogger;
use Base3\CoreFoundation\Session\Api\ISession;
use Base3\CoreFoundation\Usermanager\Api\IUsermanager;
use Base3\CoreFoundation\Usermanager\Permission;
use Base3\IliasIdentitySwitch\Exception\IdentitySwitchDeniedException;
use Base3\IliasIdentitySwitch\Feature\IliasIdentitySwitchFeature;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use ilObjUser;
use ilSession;

/** ILIAS-only identity transition owner, using its native authentication session. */
final class IdentitySwitchService {

	private const STATE = 'identityswitch.switch_record';
	private const CSRF = 'identityswitch.request_token';

	// Required native ILIAS authentication fields. BASE3 session keys are not substitutes.
	private const ACCOUNT_FIELD = 'AccountId';
	private const AUTH_FIELD = '_authsession_user_id';

	public function __construct(
		private readonly ISession $session,
		private readonly IUsermanager $usermanager,
		private readonly IAuthorizationService $authorization,
		private readonly ILogger $logger
	) {}

	public function isPermitted(): bool {
		try {
			return $this->authorization->decide(new AuthorizationRequest(
				$this->authorization->getDefaultAuthority(),
				Permission::for('feature', IliasIdentitySwitchFeature::getName())
			))->isAllowed();
		} catch (Throwable) {
			return false;
		}
	}

	/** The native user ID currently bound to the request; null if anonymous or unresolved. */
	public function currentId(): ?int {
		$user = $this->usermanager->getUser();
		$id = $user === null ? null : filter_var($user->id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
		return $id === false || $id === null ? null : (int)$id;
	}

	/**
	 * A switching record is valid only in the same native ILIAS identity it selected.
	 * It intentionally contains the minimum data needed to restore the original identity.
	 *
	 * @return array{issuer:int,subject:int}|null
	 */
	public function activeRecord(): ?array {
		if (!$this->session->started()) return null;
		$record = $this->session->get(self::STATE);
		if (!is_array($record)) return null;
		$issuer = filter_var($record['issuer'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
		$subject = filter_var($record['subject'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
		if ($issuer === false || $subject === false || $issuer === null || $subject === null || $issuer === $subject) return null;
		if ($this->currentId() !== $subject) return null;
		if ((int)ilSession::get(self::ACCOUNT_FIELD) !== $subject || (int)ilSession::get(self::AUTH_FIELD) !== $subject) return null;
		return ['issuer' => $issuer, 'subject' => $subject];
	}

	public function isActive(): bool {
		return $this->activeRecord() !== null;
	}

	public function hasSwitchRecord(): bool {
		return $this->session->has(self::STATE);
	}

	/** Issues a per-session anti-CSRF secret for the switch and restoration POST actions. */
	public function csrfToken(): string {
		$this->assertSession();
		$token = $this->session->get(self::CSRF);
		if (!is_string($token) || strlen($token) !== 64 || !ctype_xdigit($token)) {
			$token = bin2hex(random_bytes(32));
			$this->session->set(self::CSRF, $token);
		}
		return $token;
	}

	public function assertCsrf(mixed $candidate): void {
		$this->assertSession();
		$expected = $this->session->get(self::CSRF);
		if (!is_string($expected) || strlen($expected) !== 64 || !is_string($candidate) || !hash_equals($expected, $candidate)) {
			throw new IdentitySwitchDeniedException('The identity switch request was not authenticated.');
		}
	}

	/** The eligible audience is decided exclusively by IAuthorizationService. */
	public function assertMayStart(): void {
		if (!$this->isPermitted()) throw new IdentitySwitchDeniedException('The identity switch feature is not granted.');
		$this->assertSession();
		if ($this->currentId() === null) throw new IdentitySwitchDeniedException('No authenticated ILIAS identity is available.');
		if ($this->hasSwitchRecord()) throw new IdentitySwitchDeniedException('An identity switch is already recorded in this session.');
	}

	public function assertValidTarget(int $targetId): void {
		if ($targetId <= 0 || $targetId === $this->currentId() || (defined('ANONYMOUS_USER_ID') && $targetId === (int)ANONYMOUS_USER_ID)) {
			throw new InvalidArgumentException('Invalid target identity.');
		}
		if ($this->usermanager->getUserById($targetId) === null || !ilObjUser::_lookupActive($targetId)) {
			throw new InvalidArgumentException('Target identity is missing or inactive.');
		}
	}

	/**
	 * Moves the authenticated native ILIAS session to an existing active account.
	 * The caller must already have validated a POST request and its CSRF token.
	 */
	public function begin(int $targetId): void {
		$this->assertMayStart();
		$this->assertValidTarget($targetId);
		$issuer = $this->currentId();
		if ($issuer === null) throw new IdentitySwitchDeniedException('No authenticated ILIAS identity is available.');
		$this->updateNativeSession($targetId, ['issuer' => $issuer, 'subject' => $targetId]);
		$this->logger->notice('ILIAS identity switch started.', ['issuer_id' => $issuer, 'subject_id' => $targetId]);
	}

	/** Restores only the issuer recorded in this exact native session, even if its feature grant was revoked. */
	public function finish(): void {
		$record = $this->activeRecord();
		if ($record === null) throw new IdentitySwitchDeniedException('No valid active identity switch can be restored.');
		if ($this->usermanager->getUserById($record['issuer']) === null || !ilObjUser::_lookupActive($record['issuer'])) {
			throw new IdentitySwitchDeniedException('The original account is unavailable.');
		}
		$this->updateNativeSession($record['issuer'], null);
		$this->logger->notice('ILIAS identity switch finished.', ['issuer_id' => $record['issuer'], 'subject_id' => $record['subject']]);
	}

	/**
	 * Uses the existing ILIAS session and its persistence owner, never a second auth backend.
	 * On a rejected native write, restore the in-memory fields and plugin marker.
	 *
	 * @param array{issuer:int,subject:int}|null $newRecord
	 */
	private function updateNativeSession(int $nextAccount, ?array $newRecord): void {
		$this->assertSession();
		$previousAccount = ilSession::get(self::ACCOUNT_FIELD);
		$previousAuth = ilSession::get(self::AUTH_FIELD);
		$previousRecord = $this->session->get(self::STATE);
		try {
			if ($newRecord === null) $this->session->remove(self::STATE);
			else $this->session->set(self::STATE, $newRecord);
			ilSession::set(self::ACCOUNT_FIELD, $nextAccount);
			ilSession::set(self::AUTH_FIELD, $nextAccount);
			$encoded = session_encode();
			if ($encoded === false || ilSession::_writeData($this->session->getId(), $encoded) === false) {
				throw new RuntimeException('The native ILIAS session could not be persisted.');
			}
		} catch (Throwable $error) {
			if ($previousRecord === null) $this->session->remove(self::STATE);
			else $this->session->set(self::STATE, $previousRecord);
			if ($previousAccount === null) ilSession::clear(self::ACCOUNT_FIELD);
			else ilSession::set(self::ACCOUNT_FIELD, $previousAccount);
			if ($previousAuth === null) ilSession::clear(self::AUTH_FIELD);
			else ilSession::set(self::AUTH_FIELD, $previousAuth);
			$this->logger->error('Native ILIAS identity session update failed.', ['exception_class' => $error::class]);
			throw new IdentitySwitchDeniedException('The ILIAS session could not be changed.', 0, $error);
		}
	}

	private function assertSession(): void {
		if (!$this->session->started() || $this->session->getId() === '') {
			throw new IdentitySwitchDeniedException('A live native ILIAS session is required.');
		}
	}
}
