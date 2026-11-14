<?php declare(strict_types=1);

/** Test-only autoloading for the installed ILIAS 11 components/Base3 module tree. */
$moduleRoot = dirname(__DIR__);
$modulesRoot = dirname($moduleRoot);
$prefixes = [
	'Base3\\IliasIdentitySwitch\\Test\\' => __DIR__ . '/',
	'Base3\\IliasIdentitySwitch\\' => $moduleRoot . '/src/',
	'Base3\\CoreFoundation\\' => $modulesRoot . '/CoreFoundation/src/',
	'Base3\\Base3Ilias\\' => $modulesRoot . '/Base3Ilias/src/',
	'Base3\\ClientStack\\' => $modulesRoot . '/ClientStack/src/'
];
spl_autoload_register(static function(string $class) use ($prefixes): void {
	foreach ($prefixes as $prefix => $directory) {
		if (!str_starts_with($class, $prefix)) continue;
		$file = $directory . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
		if (is_file($file)) require_once $file;
		return;
	}
});

$nativeAliases = [
	'ilObjUser' => 'NativeAccountStub',
	'ilSession' => 'NativeSessionStub',
	'ILIAS\\DI\\Container' => 'NativeIliasContainerStub',
	'ILIAS\\UI\\Component\\Component' => 'NativeComponentContract',
	'ILIAS\\GlobalScreen\\Identification\\IdentificationInterface' => 'NativeIdentificationStub',
	'ILIAS\\GlobalScreen\\Scope\\MetaBar\\Factory\\MetaBarItemFactory' => 'NativeMetaBarItemFactoryStub',
	'ILIAS\\GlobalScreen\\Scope\\MetaBar\\Factory\\isItem' => 'NativeMetaBarItemStub',
	'ILIAS\\UI\\Component\\Symbol\\Icon\\Icon' => 'NativeIconStub',
	'ILIAS\\UI\\Component\\MainControls\\ModeInfo' => 'NativeModeInfoStub',
	'ILIAS\\Data\\Factory' => 'NativeDataFactoryStub'
];
foreach ($nativeAliases as $target => $fixture) {
	if (class_exists($target, false) || interface_exists($target, false)) {
		throw new \RuntimeException('Run these tests without a bootstrapped ILIAS application.');
	}
	class_alias('Base3\\IliasIdentitySwitch\\Test\\Fixture\\' . $fixture, $target);
}

if (!defined('ILIAS_HTTP_PATH')) define('ILIAS_HTTP_PATH', 'https://example.test/ilias11');
if (!defined('ANONYMOUS_USER_ID')) define('ANONYMOUS_USER_ID', 13);

if (session_status() !== PHP_SESSION_ACTIVE && !session_start()) {
	throw new \RuntimeException('Cannot start the isolated PHP test session.');
}
