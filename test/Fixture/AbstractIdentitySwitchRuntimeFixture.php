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


namespace Base3\IliasIdentitySwitch\Test\Fixture;

use Base3\CoreFoundation\Api\IRequest;
use Base3\CoreFoundation\Authorization\Api\IAuthorizationService;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationDecision;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationRequest;
use Base3\CoreFoundation\Logger\Api\ILogger;
use Base3\CoreFoundation\Session\Api\ISession;
use Base3\CoreFoundation\Usermanager\Api\IUsermanager;
use Base3\CoreFoundation\Usermanager\User;
use Base3\IliasIdentitySwitch\Service\IdentitySwitchService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/** Shared isolated native-session setup for endpoint and interface tests. */
abstract class AbstractIdentitySwitchRuntimeFixture extends TestCase {

	protected array $sessionData = [];
	protected array $knownAccounts = [101 => true, 102 => true, 103 => true];
	protected array $searchResults = [];
	protected array $searchArguments = [];
	protected int $searchCalls = 0;
	protected array $postData = [];
	protected string $httpMethod = 'POST';
	protected bool $granted = false;
	protected bool $liveSession = true;
	protected string $sessionId = 'test-session-id';
	protected bool $failUserLookup = false;
	protected bool $failSearch = false;
	protected bool $failAuthorization = false;
	protected bool $overrideUser = false;
	protected mixed $userIdOverride = null;
	protected IdentitySwitchService $switch;
	protected IRequest&MockObject $request;
	protected IUsermanager&MockObject $users;
	protected ILogger&MockObject $logger;
	protected NativeIliasContainerStub $ilias;

	protected function setUp(): void {
		parent::setUp();
		require_once __DIR__ . '/../bootstrap.php';
		if (session_status() !== PHP_SESSION_ACTIVE) session_start();
		$_SESSION = ['AccountId' => 101, '_authsession_user_id' => 101];
		\ilSession::$data = $_SESSION;
		\ilSession::$saved = [];
		\ilSession::$writeFailure = false;
		\ilObjUser::$activeAccounts = [101 => true, 102 => true, 103 => false];
		$this->sessionData = [];
		$this->knownAccounts = [101 => true, 102 => true, 103 => true];
		$this->searchResults = [];
		$this->searchArguments = [];
		$this->searchCalls = 0;
		$this->postData = [];
		$this->httpMethod = 'POST';
		$this->granted = false;
		$this->liveSession = true;
		$this->sessionId = 'test-session-id';
		$this->failUserLookup = false;
		$this->failSearch = false;
		$this->failAuthorization = false;
		$this->overrideUser = false;
		$this->userIdOverride = null;
		http_response_code(200);

		$session = $this->createMock(ISession::class);
		$session->method('started')->willReturnCallback(fn(): bool => $this->liveSession);
		$session->method('getId')->willReturnCallback(fn(): string => $this->sessionId);
		$session->method('get')->willReturnCallback(fn(string $key, mixed $default = null): mixed => $this->sessionData[$key] ?? $default);
		$session->method('has')->willReturnCallback(fn(string $key): bool => array_key_exists($key, $this->sessionData));
		$session->method('set')->willReturnCallback(function(string $key, mixed $value): void {
			$this->sessionData[$key] = $value;
			$_SESSION['base3_' . $key] = $value;
		});
		$session->method('remove')->willReturnCallback(function(string $key): void {
			unset($this->sessionData[$key], $_SESSION['base3_' . $key]);
		});

		$this->users = $this->createMock(IUsermanager::class);
		$this->users->method('getUser')->willReturnCallback(function(): ?User {
			if ($this->overrideUser) {
				return $this->userIdOverride === null ? null : User::fromArray(['id' => $this->userIdOverride]);
			}
			$id = \ilSession::get('AccountId');
			return is_int($id) && $id > 0 ? User::fromArray(['id' => $id]) : null;
		});
		$this->users->method('getUserById')->willReturnCallback(function(int|string $id): ?User {
			if ($this->failUserLookup) throw new \UnexpectedValueException('Native directory unavailable.');
			return isset($this->knownAccounts[(int)$id]) ? User::fromArray(['id' => (int)$id]) : null;
		});
		$this->users->method('searchUsers')->willReturnCallback(function(string $query, int $limit, int $offset): array {
			$this->searchCalls++;
			$this->searchArguments = [$query, $limit, $offset];
			if ($this->failSearch) throw new \UnexpectedValueException('Directory lookup failed.');
			return $this->searchResults;
		});

		$auth = $this->createMock(IAuthorizationService::class);
		$auth->method('getDefaultAuthority')->willReturn('QualitusIlias');
		$auth->method('decide')->willReturnCallback(function(AuthorizationRequest $request): AuthorizationDecision {
			if ($this->failAuthorization) throw new \UnexpectedValueException('Authority unavailable.');
			self::assertSame('feature', $request->permission->scope);
			self::assertSame('iliasidentityswitchfeature', $request->permission->permission);
			return $this->granted ? AuthorizationDecision::allow() : AuthorizationDecision::deny();
		});

		$this->logger = $this->createMock(ILogger::class);
		$this->switch = new IdentitySwitchService($session, $this->users, $auth, $this->logger);
		$this->ilias = new NativeIliasContainerStub();
		$this->request = $this->createMock(IRequest::class);
		$this->request->method('server')->willReturnCallback(fn(string $key, mixed $default = null): mixed => $key === 'REQUEST_METHOD' ? $this->httpMethod : $default);
		$this->request->method('post')->willReturnCallback(fn(string $key, mixed $default = null): mixed => $this->postData[$key] ?? $default);
	}

	protected function prepareValidRequest(string $operation = 'begin', ?int $userId = 102): void {
		$this->postData = ['csrf' => $this->switch->csrfToken(), 'operation' => $operation];
		if ($userId !== null) $this->postData['identity'] = (string)$userId;
	}
}
