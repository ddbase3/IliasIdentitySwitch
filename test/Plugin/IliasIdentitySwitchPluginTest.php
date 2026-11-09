<?php declare(strict_types=1);

namespace IliasIdentitySwitch\Test\Plugin;

use Base3\Api\IContainer;
use Base3\Authorization\Api\IAuthorizationService;
use Base3\Logger\Api\ILogger;
use Base3\Session\Api\ISession;
use Base3\Usermanager\Api\IUsermanager;
use IliasIdentitySwitch\IliasIdentitySwitchPlugin;
use IliasIdentitySwitch\Service\IdentitySwitchService;
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
