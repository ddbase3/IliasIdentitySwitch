<?php declare(strict_types=1);

namespace IliasIdentitySwitch\Test\Fixture;

final class NativeUiFactoryStub {
	public function legacy(?string $html = null): NativeLegacyFactoryStub|NativeLegacyContentStub {
		return $html === null ? new NativeLegacyFactoryStub() : new NativeLegacyContentStub($html);
	}

	public function symbol(): NativeSymbolFactoryStub {
		return new NativeSymbolFactoryStub();
	}

	public function mainControls(): NativeMainControlsFactoryStub {
		return new NativeMainControlsFactoryStub();
	}
}
