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


namespace Base3\IliasIdentitySwitch\Test\Plugin;

use Base3\CoreFoundation\Api\IContainer;
use Base3\CoreFoundation\Authorization\Api\IAuthorizationService;
use Base3\CoreFoundation\Logger\Api\ILogger;
use Base3\CoreFoundation\Session\Api\ISession;
use Base3\CoreFoundation\Usermanager\Api\IUsermanager;
use Base3\IliasIdentitySwitch\IliasIdentitySwitchPlugin;
use Base3\IliasIdentitySwitch\Service\IdentitySwitchService;
use PHPUnit\Framework\TestCase;

final class IliasIdentitySwitchPluginTest extends TestCase {

	public function testPluginNameAndCompositionRegistersExactlyTheExistingService(): void {
		self::assertSame('iliasidentityswitchplugin', IliasIdentitySwitchPlugin::getName());
		$registrations = [];
		$session = $this->createMock(ISession::class);
		$users = $this->createMock(IUsermanager::class);
		$authorization = $this->createMock(IAuthorizationService::class);
		$logger = $this->createMock(ILogger::class);
		$dependencies = [
			ISession::class => $session,
			IUsermanager::class => $users,
			IAuthorizationService::class => $authorization,
			ILogger::class => $logger
		];
		$container = $this->createMock(IContainer::class);
		$container->method('set')->willReturnCallback(function(string $name, mixed $value, int $flags = 0) use (&$registrations, $container): IContainer {
			$registrations[$name] = ['service' => $value, 'flags' => $flags];
			return $container;
		});
		$container->method('get')->willReturnCallback(static fn(string $name): mixed => $dependencies[$name]);

		$plugin = new IliasIdentitySwitchPlugin($container);
		$plugin->init();
		self::assertSame([IliasIdentitySwitchPlugin::getName(), IdentitySwitchService::class], array_keys($registrations));
		self::assertSame($plugin, $registrations[IliasIdentitySwitchPlugin::getName()]['service']);
		self::assertSame(IContainer::SHARED, $registrations[IliasIdentitySwitchPlugin::getName()]['flags']);
		self::assertSame(IContainer::SHARED, $registrations[IdentitySwitchService::class]['flags']);
		self::assertInstanceOf(\Closure::class, $registrations[IdentitySwitchService::class]['service']);
		$service = $registrations[IdentitySwitchService::class]['service']($container);
		self::assertInstanceOf(IdentitySwitchService::class, $service);
	}
}
