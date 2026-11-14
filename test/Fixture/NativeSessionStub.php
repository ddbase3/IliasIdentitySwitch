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


namespace Base3\IliasIdentitySwitch\Test\Fixture;

/** Standalone fixture for the native ILIAS session API. */
final class NativeSessionStub {

	public static array $data = [];
	public static array $saved = [];
	public static bool $writeFailure = false;

	public static function get(string $key): mixed {
		return self::$data[$key] ?? null;
	}

	public static function set(string $key, mixed $value): void {
		self::$data[$key] = $value;
		$_SESSION[$key] = $value;
	}

	public static function clear(string $key): void {
		unset(self::$data[$key], $_SESSION[$key]);
	}


	public static function _writeData(string $id, string $data): bool {
		if (self::$writeFailure) return false;
		self::$saved[$id] = $data;
		return true;
	}
}
