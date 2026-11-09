<?php declare(strict_types=1);

namespace IliasIdentitySwitch\Test\Fixture;

final class NativeLegacyContentStub implements NativeComponentContract {
	public function __construct(public readonly string $html) {}
}
