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

final class NativeMetaBarItemStub {
	public ?string $title = null;
	public ?NativeIconStub $symbol = null;
	public ?NativeComponentContract $content = null;
	private mixed $visibility = null;

	public function withTitle(string $title): self {
		$this->title = $title;
		return $this;
	}

	public function withSymbol(NativeIconStub $icon): self {
		$this->symbol = $icon;
		return $this;
	}

	public function withLegacyContent(NativeComponentContract $content): self {
		$this->content = $content;
		return $this;
	}

	public function withVisibilityCallable(callable $callback): self {
		$this->visibility = $callback;
		return $this;
	}

	public function isVisible(): bool {
		return ($this->visibility)();
	}
}
