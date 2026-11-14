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


namespace Base3\IliasIdentitySwitch;

use Base3\CoreFoundation\Api\IContainer;
use Base3\CoreFoundation\Api\IPlugin;
use Base3\CoreFoundation\Authorization\Api\IAuthorizationService;
use Base3\CoreFoundation\Logger\Api\ILogger;
use Base3\CoreFoundation\Session\Api\ISession;
use Base3\CoreFoundation\Usermanager\Api\IUsermanager;
use Base3\IliasIdentitySwitch\Service\IdentitySwitchService;

final class IliasIdentitySwitchPlugin implements IPlugin {

	public function __construct(private readonly IContainer $container) {}

	public static function getName(): string {
		return 'iliasidentityswitchplugin';
	}

	public function init(): void {
		$this->container
			->set(self::getName(), $this, IContainer::SHARED)
			->set(IdentitySwitchService::class, fn($container) => new IdentitySwitchService(
				$container->get(ISession::class),
				$container->get(IUsermanager::class),
				$container->get(IAuthorizationService::class),
				$container->get(ILogger::class)
			), IContainer::SHARED);
	}
}
