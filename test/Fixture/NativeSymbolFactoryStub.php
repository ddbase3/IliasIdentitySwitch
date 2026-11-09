<?php declare(strict_types=1);

namespace IliasIdentitySwitch\Test\Fixture;

final class NativeSymbolFactoryStub {
	public function icon(): NativeIconFactoryStub {
		return new NativeIconFactoryStub();
	}
}
