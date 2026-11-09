<?php declare(strict_types=1);

namespace IliasIdentitySwitch\Test\ModeInfo;

use Base3\Translation\Api\ITranslation;
use IliasIdentitySwitch\ModeInfo\IdentitySwitchModeInfoProvider;
use IliasIdentitySwitch\Test\Fixture\AbstractIdentitySwitchRuntimeFixture;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use IliasIdentitySwitch\Test\Fixture\NativeModeInfoStub;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class IdentitySwitchModeInfoProviderTest extends AbstractIdentitySwitchRuntimeFixture {

	private function provider(): IdentitySwitchModeInfoProvider {
		$translation = $this->createMock(ITranslation::class);
		$translation->method('translate')->willReturnCallback(
			static fn(string $set, string $section, string $key): string => $set . '.' . $section . '.' . $key
		);
		return new IdentitySwitchModeInfoProvider($this->switch, $translation, $this->ilias);
	}

	public function testNameAndInactiveSessionDoNotRenderModeInfo(): void {
		self::assertSame('identityswitchmodeinfoprovider', IdentitySwitchModeInfoProvider::getName());
		self::assertNull($this->provider()->getModeInfo());
	}

	public function testActiveSessionRendersILIASModeInfoWithReturnMarker(): void {
		$this->granted = true;
		$this->switch->begin(102);
		$this->granted = false;
		$mode = $this->provider()->getModeInfo();
		self::assertInstanceOf(NativeModeInfoStub::class, $mode);
		self::assertSame('IdentitySwitch.identityswitch.mode_title', $mode->title);
		self::assertSame(
			'https://example.test/ilias11/ilias.php?baseClass=ildashboardgui&cmd=jumpToSelectedItems&identity_switch_return=1',
			(string)$mode->returnUri
		);
	}

	public function testModeInfoMarkerUsesQuestionMarkWhenOriginalUriHasNoQuery(): void {
		$this->granted = true;
		$this->switch->begin(102);
		$this->ilias->controller->dashboardLink = 'dashboard.php';
		$mode = $this->provider()->getModeInfo();
		self::assertSame('https://example.test/ilias11/dashboard.php?identity_switch_return=1', (string)$mode->returnUri);
	}

	public function testModeInfoDisappearsAfterReturningToOriginalAccount(): void {
		$this->granted = true;
		$this->switch->begin(102);
		$this->switch->finish();
		self::assertNull($this->provider()->getModeInfo());
	}
}
