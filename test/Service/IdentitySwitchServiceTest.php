<?php declare(strict_types=1);

namespace IliasIdentitySwitch\Test\Service;

use IliasIdentitySwitch\Test\Fixture\AbstractIdentitySwitchRuntimeFixture;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use InvalidArgumentException;
use RuntimeException;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class IdentitySwitchServiceTest extends AbstractIdentitySwitchRuntimeFixture {

	public function testFeatureDeniesByDefaultAndAuthorityFailureIsNotAnAllow(): void {
		self::assertFalse($this->switch->isPermitted());
		$this->failAuthorization = true;
		self::assertFalse($this->switch->isPermitted());
		$this->failAuthorization = false;
		$this->granted = true;
		self::assertTrue($this->switch->isPermitted());
	}

	public function testUnassignedFeaturePreventsSwitchBeforeChangingNativeSession(): void {
		$this->expectException(RuntimeException::class);
		try {
			$this->switch->begin(102);
		} finally {
			self::assertSame(101, \ilSession::get('AccountId'));
		}
	}

	public function testTrustedCurrentNativeIdentityReturnsNullForUnresolvedUsers(): void {
		self::assertSame(101, $this->switch->currentId());
		$this->overrideUser = true;
		foreach ([null, 'not-a-number', 0, -2, [], 999999999999999999999.0] as $id) {
			$this->userIdOverride = $id;
			self::assertNull($this->switch->currentId());
		}
		$this->userIdOverride = '102';
		self::assertSame(102, $this->switch->currentId());
	}

	public function testSessionMustBeStartedAndHaveIdentifier(): void {
		$this->granted = true;
		$this->liveSession = false;
		self::assertNull($this->switch->activeRecord());
		try {
			$this->switch->csrfToken();
			self::fail('Missing native session was accepted.');
		} catch (RuntimeException) {
			self::assertFalse($this->switch->hasSwitchRecord());
		}
		$this->liveSession = true;
		$this->sessionId = '';
		$this->expectException(RuntimeException::class);
		$this->switch->csrfToken();
	}

	public function testPerSessionCsrfIsReusableValidHexAndReissuedWhenInvalid(): void {
		$first = $this->switch->csrfToken();
		self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $first);
		self::assertSame($first, $this->switch->csrfToken());
		$this->switch->assertCsrf($first);
		$this->sessionData['identityswitch.request_token'] = 'invalid';
		$second = $this->switch->csrfToken();
		self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $second);
		self::assertNotSame($first, $second);
	}

	public function testCsrfRejectsMissingAlteredAndNonStringCandidates(): void {
		$token = $this->switch->csrfToken();
		foreach ([null, '', 123, ['csrf' => $token], str_repeat('a', 64), substr($token, 0, 63)] as $invalid) {
			try {
				$this->switch->assertCsrf($invalid);
				self::fail('Untrusted CSRF token was accepted.');
			} catch (RuntimeException) {
				self::assertFalse($this->switch->hasSwitchRecord());
			}
		}
		$this->switch->assertCsrf($token);
		$this->sessionData['identityswitch.request_token'] = null;
		$this->expectException(RuntimeException::class);
		$this->switch->assertCsrf($token);
	}

	public function testAccessRequiresAuthenticatedUserAndNoEarlierSwitchRecord(): void {
		$this->granted = true;
		$this->overrideUser = true;
		$this->userIdOverride = null;
		try {
			$this->switch->assertMayStart();
			self::fail('Anonymous switch was accepted.');
		} catch (RuntimeException) {
			self::assertFalse($this->switch->hasSwitchRecord());
		}
		$this->overrideUser = false;
		$this->sessionData['identityswitch.switch_record'] = ['issuer' => 101, 'subject' => 102];
		$this->expectException(RuntimeException::class);
		$this->switch->assertMayStart();
	}

	public function testTargetValidationRejectsInvalidSameAnonymousMissingAndInactiveAccounts(): void {
		$this->granted = true;
		foreach ([-1, 0, 101, ANONYMOUS_USER_ID, 103, 999] as $id) {
			try {
				$this->switch->assertValidTarget($id);
				self::fail('Invalid target was accepted.');
			} catch (InvalidArgumentException) {
				self::assertSame(101, \ilSession::get('AccountId'));
			}
		}
		$this->switch->assertValidTarget(102);
	}

	public function testActiveRecordRejectsMalformedAndStaleNativeState(): void {
		self::assertNull($this->switch->activeRecord());
		$this->sessionData['identityswitch.switch_record'] = 'invalid';
		self::assertNull($this->switch->activeRecord());
		foreach ([
			[],
			['issuer' => 'bad', 'subject' => 102],
			['issuer' => 101, 'subject' => 'bad'],
			['issuer' => 102, 'subject' => 102],
			['issuer' => 101, 'subject' => 102]
		] as $record) {
			$this->sessionData['identityswitch.switch_record'] = $record;
			self::assertNull($this->switch->activeRecord());
		}
		$this->overrideUser = true;
		$this->userIdOverride = 102;
		$this->sessionData['identityswitch.switch_record'] = ['issuer' => 101, 'subject' => 102];
		self::assertNull($this->switch->activeRecord());
		\ilSession::set('AccountId', 102);
		self::assertNull($this->switch->activeRecord());
		\ilSession::set('_authsession_user_id', 102);
		self::assertSame(['issuer' => 101, 'subject' => 102], $this->switch->activeRecord());
		self::assertTrue($this->switch->isActive());
		$this->overrideUser = false;
	}

	public function testAuthorizedSwitchPersistsNativeFieldsAndReturnsAfterGrantRevocation(): void {
		$this->granted = true;
		$token = $this->switch->csrfToken();
		$this->switch->assertCsrf($token);
		$this->logger->expects(self::exactly(2))->method('notice');
		$this->switch->begin(102);
		self::assertSame(102, \ilSession::get('AccountId'));
		self::assertSame(102, \ilSession::get('_authsession_user_id'));
		self::assertSame(['issuer' => 101, 'subject' => 102], $this->switch->activeRecord());
		self::assertTrue($this->switch->isActive());
		self::assertTrue($this->switch->hasSwitchRecord());
		self::assertArrayHasKey('test-session-id', \ilSession::$saved);
		self::assertSame(session_encode(), \ilSession::$saved['test-session-id']);
		$_SESSION = [];
		self::assertTrue(session_decode(\ilSession::$saved['test-session-id']));
		self::assertSame(102, $_SESSION['AccountId']);
		self::assertSame(['issuer' => 101, 'subject' => 102], $_SESSION['base3_identityswitch.switch_record']);
		$this->granted = false;
		self::assertFalse($this->switch->isPermitted());
		$this->switch->finish();
		self::assertSame(101, \ilSession::get('AccountId'));
		self::assertSame(101, \ilSession::get('_authsession_user_id'));
		self::assertFalse($this->switch->hasSwitchRecord());
		self::assertFalse($this->switch->isActive());
	}

	public function testNestedSwitchesAreAlwaysRejected(): void {
		$this->granted = true;
		$this->switch->begin(102);
		$this->expectException(RuntimeException::class);
		$this->switch->begin(101);
	}

	public function testFinishWithoutRecordAndWithStaleIdentityIsRejected(): void {
		try {
			$this->switch->finish();
			self::fail('Missing switch record was accepted.');
		} catch (RuntimeException) {
			self::assertSame(101, \ilSession::get('AccountId'));
		}
		$this->granted = true;
		$this->switch->begin(102);
		\ilSession::set('AccountId', 101);
		self::assertFalse($this->switch->isActive());
		$this->expectException(RuntimeException::class);
		$this->switch->finish();
	}

	public function testFinishRejectsDeletedOrInactiveOriginalAccount(): void {
		$this->granted = true;
		$this->switch->begin(102);
		unset($this->knownAccounts[101]);
		try {
			$this->switch->finish();
			self::fail('Deleted issuer account was restored.');
		} catch (RuntimeException) {
			self::assertSame(102, \ilSession::get('AccountId'));
		}
		$this->knownAccounts[101] = true;
		\ilObjUser::$activeAccounts[101] = false;
		$this->expectException(RuntimeException::class);
		$this->switch->finish();
	}

	public function testFailedNativeBeginWriteRestoresOriginalIdentityAndState(): void {
		$this->granted = true;
		\ilSession::$writeFailure = true;
		$this->logger->expects(self::once())->method('error');
		try {
			$this->switch->begin(102);
			self::fail('Native write failure was accepted.');
		} catch (RuntimeException $error) {
			self::assertInstanceOf(RuntimeException::class, $error->getPrevious());
			self::assertSame(101, \ilSession::get('AccountId'));
			self::assertSame(101, \ilSession::get('_authsession_user_id'));
			self::assertFalse($this->switch->hasSwitchRecord());
			self::assertSame([], \ilSession::$saved);
		}
	}

	public function testFailedNativeReturnWriteKeepsLiveSwitchAndIssuerRecord(): void {
		$this->granted = true;
		$this->switch->begin(102);
		\ilSession::$writeFailure = true;
		$this->logger->expects(self::once())->method('error');
		try {
			$this->switch->finish();
			self::fail('Native return write failure was accepted.');
		} catch (RuntimeException) {
			self::assertSame(102, \ilSession::get('AccountId'));
			self::assertSame(102, \ilSession::get('_authsession_user_id'));
			self::assertSame(['issuer' => 101, 'subject' => 102], $this->switch->activeRecord());
		}
	}


	public function testPartialNativeAuthenticationFieldsAreRestoredOnFailedWrite(): void {
		$this->granted = true;
		\ilSession::clear('AccountId');
		\ilSession::clear('_authsession_user_id');
		$this->overrideUser = true;
		$this->userIdOverride = 101;
		\ilSession::$writeFailure = true;
		$this->expectException(RuntimeException::class);
		try {
			$this->switch->begin(102);
		} finally {
			self::assertNull(\ilSession::get('AccountId'));
			self::assertNull(\ilSession::get('_authsession_user_id'));
		}
	}
}
