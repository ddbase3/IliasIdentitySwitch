<?php declare(strict_types=1);

namespace IliasIdentitySwitch\Test\Translation;

use PHPUnit\Framework\TestCase;

final class IdentitySwitchTranslationsTest extends TestCase {

	private const LANGUAGES = ['ar', 'bg', 'de', 'en', 'es', 'fr', 'hi', 'it', 'pl', 'pt', 'ru', 'zh'];

	/** @return array<string,array<string,string>> */
	private function catalog(string $language): array {
		$path = dirname(__DIR__, 2) . '/lang/IdentitySwitch/' . $language . '.ini';
		$catalog = parse_ini_file($path, true, INI_SCANNER_RAW);
		self::assertIsArray($catalog, $path);
		return $catalog;
	}

	public function testLanguageListMatchesTheTwelveSupportedClientStackLanguages(): void {
		$files = glob(dirname(__DIR__, 2) . '/lang/IdentitySwitch/*.ini');
		self::assertIsArray($files);
		$actual = array_map(static fn(string $file): string => basename($file, '.ini'), $files);
		sort($actual);
		self::assertSame(self::LANGUAGES, $actual);
	}

	public function testEveryLanguageHasEveryKeyUsedByTheFeatureAndSelectWidget(): void {
		$english = $this->catalog('en');
		self::assertCount(2, $english);
		self::assertCount(2, $english['iliasidentityswitchfeature']);
		self::assertCount(29, $english['identityswitch']);
		foreach (self::LANGUAGES as $language) {
			$catalog = $this->catalog($language);
			self::assertSame(array_keys($english), array_keys($catalog), $language);
			foreach ($english as $section => $keys) {
				self::assertSame(array_keys($keys), array_keys($catalog[$section]), "$language/$section");
				foreach ($keys as $key => $englishValue) {
					$translation = $catalog[$section][$key];
					self::assertIsString($translation);
					self::assertNotSame('', trim($translation), "$language/$section.$key");
					preg_match_all('/\{[a-z_]+\}/', $englishValue, $baselinePlaceholders);
					preg_match_all('/\{[a-z_]+\}/', $translation, $translatedPlaceholders);
					self::assertSame($baselinePlaceholders[0], $translatedPlaceholders[0], "$language/$section.$key");
				}
			}
		}
	}

	public function testLocalizationsAreRealTranslationsNotEnglishCatalogCopies(): void {
		$english = $this->catalog('en');
		foreach (self::LANGUAGES as $language) {
			if ($language === 'en') continue;
			$translated = $this->catalog($language);
			self::assertNotSame($english['identityswitch']['choose_heading'], $translated['identityswitch']['choose_heading'], $language);
			self::assertNotSame($english['identityswitch']['action_error'], $translated['identityswitch']['action_error'], $language);
		}
	}

	public function testClientStackWidgetAndModeInfoTranslationKeysExistInAllLanguages(): void {
		$keys = [
			'page_title', 'choose_heading', 'choose_description', 'restore_heading', 'restore_description',
			'restore_notice', 'search_label', 'search_placeholder', 'search_error', 'action_error',
			'no_selection', 'options', 'loading', 'no_results', 'load_more', 'required',
			'switch_button', 'restore_button', 'cancel_button', 'open_label', 'return_label', 'mode_title',
			'show_options', 'hide_options', 'remove_item', 'removed_item', 'backspace_hint', 'min_chars', 'results_count'
		];
		foreach (self::LANGUAGES as $language) {
			$catalog = $this->catalog($language);
			self::assertSame($keys, array_keys($catalog['identityswitch']), $language);
		}
	}
}
