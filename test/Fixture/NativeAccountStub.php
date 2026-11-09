<?php declare(strict_types=1);

namespace IliasIdentitySwitch\Test\Fixture;

/** Standalone fixture for the ILIAS user activity API. */
final class NativeAccountStub {

	public static array $activeAccounts = [];

	public static function _lookupActive(int $id): bool {
		return self::$activeAccounts[$id] ?? false;
	}
}
