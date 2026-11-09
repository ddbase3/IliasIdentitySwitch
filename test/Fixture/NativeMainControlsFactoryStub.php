<?php declare(strict_types=1);

namespace IliasIdentitySwitch\Test\Fixture;

final class NativeMainControlsFactoryStub {
	public function modeInfo(string $title, NativeUriStub $returnUri): NativeModeInfoStub {
		return new NativeModeInfoStub($title, $returnUri);
	}
}
