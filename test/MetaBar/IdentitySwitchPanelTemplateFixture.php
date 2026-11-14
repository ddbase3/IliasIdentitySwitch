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


namespace Base3\IliasIdentitySwitch\Test\MetaBar;

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
