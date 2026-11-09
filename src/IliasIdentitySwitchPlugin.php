<?php declare(strict_types=1);

namespace IliasIdentitySwitch;

use Base3\Api\IContainer;
use Base3\Api\IPlugin;
use Base3\Authorization\Api\IAuthorizationService;
use Base3\Logger\Api\ILogger;
use Base3\Session\Api\ISession;
use Base3\Usermanager\Api\IUsermanager;
use IliasIdentitySwitch\Service\IdentitySwitchService;

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
