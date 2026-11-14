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


namespace Base3\IliasIdentitySwitch\Test\ModeInfo;

use Base3\CoreFoundation\Translation\Api\ITranslation;
use Base3\IliasIdentitySwitch\ModeInfo\IdentitySwitchModeInfoProvider;
use Base3\IliasIdentitySwitch\Test\Fixture\AbstractIdentitySwitchRuntimeFixture;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Base3\IliasIdentitySwitch\Test\Fixture\NativeModeInfoStub;

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
