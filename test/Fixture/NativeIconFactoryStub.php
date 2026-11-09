<?php declare(strict_types=1);

namespace IliasIdentitySwitch\Test\Fixture;

final class NativeIconFactoryStub {
	public function custom(string $path, string $label): NativeIconStub {
		return new NativeIconStub($path, $label);
	}
}
