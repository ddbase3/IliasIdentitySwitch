<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of IliasIdentitySwitch for BASE3 Framework.
 *
 * IliasIdentitySwitch manages temporary native ILIAS identities.
 * It provides authorized session switching and safe restoration.
 * Native ILIAS permissions continue to govern every operation.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0-or-later
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 ***********************************************************************/


namespace Base3\IliasIdentitySwitch\Test\Feature;

use Base3\CoreFoundation\Translation\Api\ITranslation;
use Base3\IliasIdentitySwitch\Feature\IliasIdentitySwitchFeature;
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
