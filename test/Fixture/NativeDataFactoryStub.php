<?php declare(strict_types=1);

namespace IliasIdentitySwitch\Test\Fixture;

final class NativeDataFactoryStub {
	public function uri(string $value): NativeUriStub {
		return new NativeUriStub($value);
	}
}
