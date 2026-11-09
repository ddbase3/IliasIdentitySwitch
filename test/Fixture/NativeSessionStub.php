<?php declare(strict_types=1);

namespace IliasIdentitySwitch\Test\Fixture;

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
