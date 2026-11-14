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


namespace Base3\IliasIdentitySwitch\MetaBar;

use Base3\CoreFoundation\Api\IAssetResolver;
use Base3\CoreFoundation\Api\IMvcView;
use Base3\CoreFoundation\Api\ISystemService;
use Base3\CoreFoundation\LinkTarget\Api\ILinkTargetService;
use Base3\CoreFoundation\Translation\Api\ITranslation;
use Base3\Base3Ilias\Api\IMetaBarItemProvider;
use Base3\Base3Ilias\Ilias\Base3IliasLegacyContentAdapter;
use Base3\IliasIdentitySwitch\Output\IdentitySwitchCommandOutput;
use Base3\IliasIdentitySwitch\Output\IdentitySwitchDirectoryOutput;
use Base3\IliasIdentitySwitch\Service\IdentitySwitchService;
use ILIAS\DI\Container as IliasContainer;
use ILIAS\GlobalScreen\Identification\IdentificationInterface;
use ILIAS\GlobalScreen\Scope\MetaBar\Factory\isItem;
use ILIAS\GlobalScreen\Scope\MetaBar\Factory\MetaBarItemFactory;
use ILIAS\UI\Component\Symbol\Icon\Icon;

/** Renders the native ILIAS MetaBar slate for the feature-gated identity switch. */
final class IdentitySwitchMetaBarItemProvider implements IMetaBarItemProvider {

	public function __construct(
		private readonly IdentitySwitchService $switch,
		private readonly ITranslation $translation,
		private readonly ILinkTargetService $links,
		private readonly IAssetResolver $assets,
		private readonly IMvcView $view,
		private readonly ISystemService $system,
		private readonly IliasContainer $ilias
	) {}

	public static function getName(): string {
		return 'identityswitchmetabaritemprovider';
	}

	public function getMetaBarItem(MetaBarItemFactory $metaBar, IdentificationInterface $identification): isItem {
		$active = $this->switch->isActive();
		$visible = $active || ($this->switch->isPermitted() && !$this->switch->hasSwitchRecord());
		$title = $this->translation->translate(
			'IdentitySwitch', 'identityswitch', $active ? 'return_label' : 'open_label'
		);
		$iconPath = $this->assets->resolve('plugin/ClientStack/assets/icons/person.svg');

		return $metaBar->topLegacyItem($identification)
			->withTitle($title)
			->withSymbol($this->ilias->ui()->factory()->symbol()->icon()->custom($iconPath, $title)->withSize(Icon::MEDIUM))
			->withLegacyContent(Base3IliasLegacyContentAdapter::create($this->system, $this->ilias, $visible ? $this->renderPanel($active) : ''))
			->withVisibilityCallable(static fn(): bool => $visible);
	}

	private function renderPanel(bool $active): string {
		$this->view->setPath(dirname(__DIR__, 2));
		$this->view->loadBricks('IdentitySwitch');
		$this->view->setTemplate('Output/IdentitySwitchPanel.php');
		$this->view->assign('phrases', $this->view->getBricks('identityswitch') ?? []);
		$this->view->assign('active', $active);
		$this->view->assign('token', $this->switch->csrfToken());
		$this->view->assign('searchUrl', $this->links->getLink([
			'name' => IdentitySwitchDirectoryOutput::getName(), 'out' => 'json'
		]));
		$this->view->assign('commandUrl', $this->links->getLink([
			'name' => IdentitySwitchCommandOutput::getName(), 'out' => 'json'
		]));
		$this->view->assign('widgetJs', $this->assets->resolve('plugin/ClientStack/assets/modularselect/index.js'));
		$this->view->assign('widgetCss', $this->assets->resolve('plugin/ClientStack/assets/modularselect/styles/modularselect.css'));
		$this->view->assign('pageCss', $this->assets->resolve('plugin/IliasIdentitySwitch/assets/identity-switch.css'));
		$this->view->assign('pageJs', $this->assets->resolve('plugin/IliasIdentitySwitch/assets/identity-switch.js'));

		return $this->view->loadTemplate();
	}
}
