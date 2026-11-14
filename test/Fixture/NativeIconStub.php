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

final class NativeIconStub {
	public const MEDIUM = 'medium';
	public ?string $size = null;

	public function __construct(public readonly string $path, public readonly string $label) {}

	public function withSize(string $size): self {
		$this->size = $size;
		return $this;
	}
}
