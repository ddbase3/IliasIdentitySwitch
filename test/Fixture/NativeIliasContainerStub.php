<?php declare(strict_types=1);

namespace IliasIdentitySwitch\Test\Fixture;

final class NativeIliasContainerStub {
	public NativeCtrlStub $controller;

	public function __construct() {
		$this->controller = new NativeCtrlStub();
	}

	public function ui(): NativeUiRootStub {
		return new NativeUiRootStub();
	}

	public function ctrl(): NativeCtrlStub {
		return $this->controller;
	}
}
