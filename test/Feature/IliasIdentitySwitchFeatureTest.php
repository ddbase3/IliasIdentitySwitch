<?php declare(strict_types=1);

namespace IliasIdentitySwitch\Test\Feature;

use Base3\Translation\Api\ITranslation;
use IliasIdentitySwitch\Feature\IliasIdentitySwitchFeature;
use PHPUnit\Framework\TestCase;

final class IliasIdentitySwitchFeatureTest extends TestCase {

	public function testFeatureIsUnassignedByDefaultAndOwnsOneExplicitPermission(): void {
		$translation = $this->createMock(ITranslation::class);
		$translation->method('translate')->willReturnCallback(
			static fn(string $set, string $section, string $key): string => "$set.$section.$key"
		);
		$feature = new IliasIdentitySwitchFeature($translation);
		self::assertSame('iliasidentityswitchfeature', $feature::getName());
		self::assertSame([], $feature->getDefaultAudience());
		self::assertCount(1, $feature->getPermissions());
		self::assertSame('feature', $feature->getPermissions()[0]->scope);
		self::assertSame('iliasidentityswitchfeature', $feature->getPermissions()[0]->permission);
		self::assertNotSame('', $feature->getLabel());
		self::assertNotSame('', $feature->getDescription());
	}
}
