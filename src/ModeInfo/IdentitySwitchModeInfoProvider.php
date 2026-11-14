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


namespace Base3\IliasIdentitySwitch\ModeInfo;

use Base3\CoreFoundation\Translation\Api\ITranslation;
use Base3\Base3Ilias\Api\IModeInfoProvider;
use Base3\IliasIdentitySwitch\Service\IdentitySwitchService;
use ILIAS\Data\Factory as DataFactory;
use ILIAS\DI\Container as IliasContainer;
use ILIAS\UI\Component\MainControls\ModeInfo;

/** Displays the native ILIAS mode notice while another account is active. */
final class IdentitySwitchModeInfoProvider implements IModeInfoProvider {

	public function __construct(
		private readonly IdentitySwitchService $switch,
		private readonly ITranslation $translation,
		private readonly IliasContainer $ilias
	) {}

	public static function getName(): string {
		return 'identityswitchmodeinfoprovider';
	}

	public function getModeInfo(): ?ModeInfo {
		if (!$this->switch->isActive()) return null;

		$title = $this->translation->translate('IdentitySwitch', 'identityswitch', 'mode_title');
		$url = $this->ilias->ctrl()->getLinkTargetByClass(\ilDashboardGUI::class, 'jumpToSelectedItems');
		$url .= (str_contains($url, '?') ? '&' : '?') . 'identity_switch_return=1';
		$absolute = rtrim(ILIAS_HTTP_PATH, '/') . '/' . ltrim($url, '/');

		// The browser handles this action with the same protected POST as the MetaBar return button.
		// A plain GET never changes the authenticated ILIAS account.
		return $this->ilias->ui()->factory()->mainControls()->modeInfo(
			$title,
			(new DataFactory())->uri($absolute)
		);
	}
}
