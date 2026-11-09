<?php declare(strict_types=1);

namespace IliasIdentitySwitch\Test\Fixture;

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
