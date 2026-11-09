<?php declare(strict_types=1);

namespace IliasIdentitySwitch\Test\Fixture;

final class NativeUriStub {
	public function __construct(public readonly string $value) {}

	public function __toString(): string {
		return $this->value;
	}
}
