<?php declare(strict_types=1);

namespace IliasIdentitySwitch\Test\Output;

use IliasIdentitySwitch\Output\IdentitySwitchCommandOutput;
use IliasIdentitySwitch\Test\Fixture\AbstractIdentitySwitchRuntimeFixture;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class IdentitySwitchCommandOutputTest extends AbstractIdentitySwitchRuntimeFixture {

	private function endpoint(): IdentitySwitchCommandOutput {
		return new IdentitySwitchCommandOutput($this->switch, $this->request, $this->ilias, $this->logger);
	}

	public function testNameAndOutputFormatGuard(): void {
		self::assertSame('identityswitchcommandoutput', IdentitySwitchCommandOutput::getName());
		self::assertSame('', $this->endpoint()->getOutput('html'));
		self::assertSame(404, http_response_code());
	}

	public function testMutationRequiresPost(): void {
		$this->httpMethod = 'GET';
		self::assertSame(['success' => false, 'redirect' => ''], json_decode($this->endpoint()->getOutput('json'), true));
		self::assertSame(405, http_response_code());
	}

	public function testMutationRequiresValidCsrfBeforeExaminingOperation(): void {
		$this->postData = ['operation' => 'begin', 'identity' => 102, 'csrf' => 'invalid'];
		self::assertSame(['success' => false, 'redirect' => ''], json_decode($this->endpoint()->getOutput('json'), true));
		self::assertSame(403, http_response_code());
		self::assertFalse($this->switch->hasSwitchRecord());
	}

	public function testInvalidOperationsAndIdentifiersReturnUnprocessableEntity(): void {
		$this->granted = true;
		$this->prepareValidRequest();
		$cases = [
			['operation' => 'unknown', 'identity' => 102],
			['operation' => 'begin', 'identity' => '-1'],
			['operation' => 'begin', 'identity' => 0],
			['operation' => 'begin', 'identity' => '001'],
			['operation' => 'begin', 'identity' => 'text'],
			['operation' => 'begin', 'identity' => []],
			['operation' => 'begin', 'identity' => '99999999999999999999999'],
			['operation' => 'begin', 'identity' => 101],
			['operation' => 'begin', 'identity' => 103],
			['operation' => 'begin', 'identity' => 999]
		];
		foreach ($cases as $case) {
			$this->postData = $case + ['csrf' => $this->switch->csrfToken()];
			self::assertSame(['success' => false, 'redirect' => ''], json_decode($this->endpoint()->getOutput('json'), true));
			self::assertSame(422, http_response_code());
			self::assertFalse($this->switch->hasSwitchRecord());
		}
	}

	public function testUnauthorizedAccountSwitchReturnsForbidden(): void {
		$this->prepareValidRequest();
		self::assertSame(['success' => false, 'redirect' => ''], json_decode($this->endpoint()->getOutput('json'), true));
		self::assertSame(403, http_response_code());
	}

	public function testBeginReturnsNativeRedirectAndPersistsSubject(): void {
		$this->granted = true;
		$this->prepareValidRequest();
		self::assertSame([
			'success' => true,
			'redirect' => 'ilias.php?baseClass=ildashboardgui&cmd=jumpToSelectedItems'
		], json_decode($this->endpoint()->getOutput('json'), true));
		self::assertSame(200, http_response_code());
		self::assertSame(102, \ilSession::get('AccountId'));
		self::assertTrue($this->switch->isActive());
	}

	public function testReturnUsesSameProtectedEndpointWithoutTargetFeatureGrant(): void {
		$this->granted = true;
		$this->switch->begin(102);
		$this->granted = false;
		$this->prepareValidRequest('finish', null);
		self::assertSame([
			'success' => true,
			'redirect' => 'ilias.php?baseClass=ildashboardgui&cmd=jumpToSelectedItems'
		], json_decode($this->endpoint()->getOutput('json'), true));
		self::assertSame(101, \ilSession::get('AccountId'));
		self::assertFalse($this->switch->isActive());
	}

	public function testFinishWithoutLiveRecordIsForbidden(): void {
		$this->prepareValidRequest('finish', null);
		self::assertSame(['success' => false, 'redirect' => ''], json_decode($this->endpoint()->getOutput('json'), true));
		self::assertSame(403, http_response_code());
	}

	public function testUnexpectedNativeFailuresAreLoggedButNotExposedToBrowser(): void {
		$this->granted = true;
		$this->prepareValidRequest();
		$this->failUserLookup = true;
		$this->logger->expects(self::once())->method('error')->with(
			'ILIAS identity switch action failed.',
			self::callback(static fn(array $context): bool =>
				($context['exception_class'] ?? '') === \UnexpectedValueException::class
				&& str_contains((string)($context['exception_message'] ?? ''), 'Native directory unavailable')
			)
		);
		self::assertSame(['success' => false, 'redirect' => ''], json_decode($this->endpoint()->getOutput('json'), true));
		self::assertSame(500, http_response_code());
		self::assertSame(101, \ilSession::get('AccountId'));
	}
	public function testNativeWriteFailureReturnsForbiddenAndDoesNotPartiallySwitch(): void {
		$this->granted = true;
		$this->prepareValidRequest();
		\ilSession::$writeFailure = true;
		self::assertSame(['success' => false, 'redirect' => ''], json_decode($this->endpoint()->getOutput('json'), true));
		self::assertSame(403, http_response_code());
		self::assertSame(101, \ilSession::get('AccountId'));
		self::assertFalse($this->switch->hasSwitchRecord());
	}

}
