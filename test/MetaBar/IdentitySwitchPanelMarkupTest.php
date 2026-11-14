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

use PHPUnit\Framework\TestCase;

final class IdentitySwitchPanelMarkupTest extends TestCase {

	/** @return array<string,mixed> */
	private function values(bool $active): array {
		return [
			'active' => $active,
			'phrases' => [
				'choose_description' => 'Select an account',
				'restore_description' => 'Working as another account',
				'restore_notice' => 'Return now',
				'switch_button' => 'Switch account',
				'restore_button' => 'Return to my account',
				'search_label' => 'Account'
			],
			'token' => str_repeat('a', 64),
			'commandUrl' => './index.php?out=json&name=identityswitchcommandoutput',
			'searchUrl' => './index.php?out=json&name=identityswitchdirectoryoutput',
			'widgetJs' => './components/Base3/ClientStack/modularselect/index.js',
			'widgetCss' => './components/Base3/ClientStack/modularselect/styles/modularselect.css',
			'pageCss' => './components/Base3/IliasIdentitySwitch/identity-switch.css',
			'pageJs' => './components/Base3/IliasIdentitySwitch/identity-switch.js'
		];
	}

	public function testSelectionRendersOnlySlateContentWithAjaxSearch(): void {
		$html = (new IdentitySwitchPanelTemplateFixture())->render($this->values(false));
		self::assertStringContainsString('data-identity-switch-menu', $html);
		self::assertStringContainsString('data-identity-switch-select', $html);
		self::assertStringContainsString('name="operation" value="begin"', $html);
		self::assertStringContainsString('name="identity"', $html);
		self::assertStringContainsString('type="module"', $html);
		self::assertStringNotContainsString('<html', $html);
		self::assertStringNotContainsString('<body', $html);
		self::assertStringNotContainsString('Back to ILIAS', $html);
	}

	public function testActiveSwitchShowsOnlyProtectedReturnAction(): void {
		$html = (new IdentitySwitchPanelTemplateFixture())->render($this->values(true));
		self::assertStringContainsString('name="operation" value="finish"', $html);
		self::assertStringContainsString('Return to my account', $html);
		self::assertStringNotContainsString('data-identity-switch-select', $html);
	}

	public function testEmbeddedJsonEscapesMarkup(): void {
		$values = $this->values(false);
		$values['phrases']['choose_description'] = '</script><script>alert(1)</script>';
		$html = (new IdentitySwitchPanelTemplateFixture())->render($values);
		self::assertStringNotContainsString('<script>alert(1)</script>', $html);
		self::assertStringContainsString('&lt;/script&gt;', $html);
	}
}
