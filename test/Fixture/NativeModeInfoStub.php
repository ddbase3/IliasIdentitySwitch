<?php declare(strict_types=1);

namespace IliasIdentitySwitch\Test\Fixture;

final class NativeModeInfoStub {
	public function __construct(public readonly string $title, public readonly NativeUriStub $returnUri) {}
}
