<?php declare(strict_types=1);

/** Isolated PHPUnit bootstrap. Native ILIAS classes are never declared in source files. */
$componentRoot = dirname(__DIR__);
$modulesRoot = dirname($componentRoot);

spl_autoload_register(static function(string $class) use ($componentRoot, $modulesRoot): void {
	$roots = [
		'IliasIdentitySwitch\\Test\\' => $componentRoot . '/test/',
		'IliasIdentitySwitch\\' => $componentRoot . '/src/',
		'Base3\\' => $modulesRoot . '/Base3Framework/src/',
		'Base3Ilias\\' => $modulesRoot . '/Base3Ilias/src/'
	];
	foreach ($roots as $namespace => $directory) {
		if (!str_starts_with($class, $namespace)) continue;
		$file = $directory . str_replace('\\', '/', substr($class, strlen($namespace))) . '.php';
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
	class_alias('IliasIdentitySwitch\\Test\\Fixture\\' . $fixture, $target);
}

if (!defined('ILIAS_HTTP_PATH')) define('ILIAS_HTTP_PATH', 'https://example.test/ilias11');
if (!defined('ANONYMOUS_USER_ID')) define('ANONYMOUS_USER_ID', 13);

if (session_status() !== PHP_SESSION_ACTIVE && !session_start()) {
	throw new \RuntimeException('Cannot start the isolated PHP test session.');
}
