<?php declare(strict_types=1);

namespace IliasIdentitySwitch\Test\MetaBar;

use Base3\Api\IAssetResolver;
use Base3\Api\IMvcView;
use Base3\Api\ISystemService;
use Base3\LinkTarget\Api\ILinkTargetService;
use Base3\Translation\Api\ITranslation;
use IliasIdentitySwitch\MetaBar\IdentitySwitchMetaBarItemProvider;
use IliasIdentitySwitch\Test\Fixture\AbstractIdentitySwitchRuntimeFixture;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use IliasIdentitySwitch\Test\Fixture\NativeIdentificationStub;
use IliasIdentitySwitch\Test\Fixture\NativeLegacyContentStub;
use IliasIdentitySwitch\Test\Fixture\NativeMetaBarItemFactoryStub;
use IliasIdentitySwitch\Test\Fixture\NativeMetaBarItemStub;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class IdentitySwitchMetaBarItemProviderTest extends AbstractIdentitySwitchRuntimeFixture {

	private string $hostVersion = '11.3';
	private array $assigned = [];
	private array $resolvedAssets = [];
	private int $renderCount = 0;

	protected function setUp(): void {
		parent::setUp();
		$this->hostVersion = '11.3';
		$this->assigned = [];
		$this->resolvedAssets = [];
		$this->renderCount = 0;
	}

	private function provider(): IdentitySwitchMetaBarItemProvider {
		$translator = $this->createMock(ITranslation::class);
		$translator->method('translate')->willReturnCallback(static fn(string $set, string $section, string $key): string => 'translated_' . $key);

		$links = $this->createMock(ILinkTargetService::class);
		$links->method('getLink')->willReturnCallback(static fn(array $target): string => '/endpoint?name=' . $target['name'] . '&out=' . $target['out']);

		$assets = $this->createMock(IAssetResolver::class);
		$assets->method('resolve')->willReturnCallback(function(string $path): string {
			$this->resolvedAssets[] = $path;
			return '/public/' . $path;
		});

		$view = $this->createMock(IMvcView::class);
		$view->method('getBricks')->willReturn(['choose_description' => 'Select an account']);
		$view->method('assign')->willReturnCallback(function(string $key, mixed $value): void {
			$this->assigned[$key] = $value;
		});
		$view->method('loadTemplate')->willReturnCallback(function(): string {
			$this->renderCount++;
			return '<section>Rendered identity switch</section>';
		});

		$system = $this->createMock(ISystemService::class);
		$system->method('getHostSystemName')->willReturn('ILIAS');
		$system->method('getHostSystemVersion')->willReturnCallback(fn(): string => $this->hostVersion);

		return new IdentitySwitchMetaBarItemProvider(
			$this->switch, $translator, $links, $assets, $view, $system, $this->ilias
		);
	}

	public function testNameAndFeatureDisabledHideTheNativeSlate(): void {
		self::assertSame('identityswitchmetabaritemprovider', IdentitySwitchMetaBarItemProvider::getName());
		$factory = new NativeMetaBarItemFactoryStub();
		$id = new NativeIdentificationStub();
		$item = $this->provider()->getMetaBarItem($factory, $id);
		self::assertInstanceOf(NativeMetaBarItemStub::class, $item);
		self::assertSame($id, $factory->lastIdentification);
		self::assertSame(1, $factory->calls);
		self::assertFalse($item->isVisible());
		self::assertSame('', $item->content->html);
		self::assertSame(0, $this->renderCount);
		self::assertSame('translated_open_label', $item->title);
	}

	public function testGrantedFeatureRendersNativeSlateUsingResolvedClientStackResources(): void {
		$this->granted = true;
		$item = $this->provider()->getMetaBarItem(new NativeMetaBarItemFactoryStub(), new NativeIdentificationStub());
		self::assertTrue($item->isVisible());
		self::assertSame('translated_open_label', $item->title);
		self::assertSame('/public/plugin/ClientStack/assets/icons/person.svg', $item->symbol->path);
		self::assertSame('medium', $item->symbol->size);
		self::assertInstanceOf(NativeLegacyContentStub::class, $item->content);
		self::assertSame('<section>Rendered identity switch</section>', $item->content->html);
		self::assertSame(1, $this->renderCount);
		self::assertFalse($this->assigned['active']);
		self::assertSame('Select an account', $this->assigned['phrases']['choose_description']);
		self::assertSame('/endpoint?name=identityswitchdirectoryoutput&out=json', $this->assigned['searchUrl']);
		self::assertSame('/endpoint?name=identityswitchcommandoutput&out=json', $this->assigned['commandUrl']);
		foreach (['modularselect/index.js', 'modularselect/styles/modularselect.css'] as $subpath) {
			self::assertContains('plugin/ClientStack/assets/' . $subpath, $this->resolvedAssets);
		}
		self::assertContains('plugin/IliasIdentitySwitch/assets/identity-switch.js', $this->resolvedAssets);
		self::assertContains('plugin/IliasIdentitySwitch/assets/identity-switch.css', $this->resolvedAssets);
	}

	public function testActiveSwitchShowsReturnSlateWithoutNewPermission(): void {
		$this->granted = true;
		$this->switch->begin(102);
		$this->granted = false;
		$item = $this->provider()->getMetaBarItem(new NativeMetaBarItemFactoryStub(), new NativeIdentificationStub());
		self::assertTrue($item->isVisible());
		self::assertSame('translated_return_label', $item->title);
		self::assertTrue($this->assigned['active']);
		self::assertSame('<section>Rendered identity switch</section>', $item->content->html);
	}

	public function testOrphanedRecordIsNotASecondSwitchEntryPoint(): void {
		$this->granted = true;
		$this->switch->begin(102);
		\ilSession::set('AccountId', 101);
		$item = $this->provider()->getMetaBarItem(new NativeMetaBarItemFactoryStub(), new NativeIdentificationStub());
		self::assertFalse($item->isVisible());
		self::assertSame(0, $this->renderCount);
	}

	public function testExistingBase3IliasAdapterHandlesTheOlderNativeLegacyFactory(): void {
		$this->hostVersion = '9.4';
		$this->granted = true;
		$item = $this->provider()->getMetaBarItem(new NativeMetaBarItemFactoryStub(), new NativeIdentificationStub());
		self::assertInstanceOf(NativeLegacyContentStub::class, $item->content);
		self::assertSame('<section>Rendered identity switch</section>', $item->content->html);
	}

	public function testExistingBase3IliasAdapterHandlesModernNativeLegacyFactory(): void {
		$this->hostVersion = '12.0';
		$this->granted = true;
		$item = $this->provider()->getMetaBarItem(new NativeMetaBarItemFactoryStub(), new NativeIdentificationStub());
		self::assertInstanceOf(NativeLegacyContentStub::class, $item->content);
	}
}
