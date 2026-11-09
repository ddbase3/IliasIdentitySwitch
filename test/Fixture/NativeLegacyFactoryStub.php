<?php declare(strict_types=1);

namespace IliasIdentitySwitch\Test\Fixture;

final class NativeLegacyFactoryStub {
	public function content(string $html): NativeLegacyContentStub {
		return new NativeLegacyContentStub($html);
	}
}
