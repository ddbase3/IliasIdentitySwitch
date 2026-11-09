<?php declare(strict_types=1);

namespace IliasIdentitySwitch\Test\Fixture;

final class NativeCtrlStub {
	public string $dashboardLink = 'ilias.php?baseClass=ildashboardgui&cmd=jumpToSelectedItems';

	public function getLinkTargetByClass(string $class, string $command): string {
		return $this->dashboardLink;
	}
}
