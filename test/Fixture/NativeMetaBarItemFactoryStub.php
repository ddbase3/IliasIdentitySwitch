<?php declare(strict_types=1);

namespace IliasIdentitySwitch\Test\Fixture;

final class NativeMetaBarItemFactoryStub {
	public int $calls = 0;
	public ?NativeIdentificationStub $lastIdentification = null;

	public function topLegacyItem(NativeIdentificationStub $id): NativeMetaBarItemStub {
		$this->calls++;
		$this->lastIdentification = $id;
		return new NativeMetaBarItemStub();
	}
}
