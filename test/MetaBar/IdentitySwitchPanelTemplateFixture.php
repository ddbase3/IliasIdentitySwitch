<?php declare(strict_types=1);

namespace IliasIdentitySwitch\Test\MetaBar;

/** Renders the product template without loading any global ILIAS classes. */
final class IdentitySwitchPanelTemplateFixture {

	/** @var array<string,mixed> */
	public array $_ = [];

	/** @param array<string,mixed> $values */
	public function render(array $values): string {
		$this->_ = $values;
		ob_start();
		try {
			require dirname(__DIR__, 2) . '/tpl/Output/IdentitySwitchPanel.php';
			return (string)ob_get_clean();
		} catch (\Throwable $error) {
			ob_end_clean();
			throw $error;
		}
	}
}
