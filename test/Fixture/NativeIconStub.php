<?php declare(strict_types=1);

namespace IliasIdentitySwitch\Test\Fixture;

final class NativeIconStub {
	public const MEDIUM = 'medium';
	public ?string $size = null;

	public function __construct(public readonly string $path, public readonly string $label) {}

	public function withSize(string $size): self {
		$this->size = $size;
		return $this;
	}
}
