<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Unit\Theme;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Garde-fous du mode sombre optionnel (US-226) : il doit couvrir toutes les pages du thème et rester dans sa portée.
 */
final class DarkModeStylesTest extends TestCase
{
    private const STYLES = __DIR__ . '/../../../assets/styles';

    /**
     * @return iterable<string, array{string}>
     */
    public static function pageStylesheetsProvider(): iterable
    {
        foreach (glob(self::STYLES . '/pages/_*.scss') ?: [] as $file) {
            yield basename($file) => [$file];
        }
    }

    #[DataProvider('pageStylesheetsProvider')]
    public function testPageStylesheetReadsColorsFromCustomPropertiesSoDarkModeCanRedefineThem(string $file): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/\$industry-(?:navy|muted|white|surface-strong|surface|accent|border|success|danger|paper|ink)\b(?!-)/',
            (string) file_get_contents($file),
            basename($file) . ' lit une couleur dans une variable SCSS : le mode sombre ne peut pas la redéfinir',
        );
    }

    public function testDarkTokensApplyOnlyInsideTheThemeScope(): void
    {
        $theme = (string) file_get_contents(self::STYLES . '/theme.scss');

        $this->assertStringContainsString("[data-industry-mode='dark']", $theme);
        $this->assertStringContainsString('@media (prefers-color-scheme: dark)', $theme);
        $this->assertStringNotContainsString(':root', $theme);
    }

    public function testSystemPreferenceStepsAsideWhenTheVisitorChoseAMode(): void
    {
        $theme = (string) file_get_contents(self::STYLES . '/theme.scss');

        $this->assertStringContainsString('html:not([data-industry-mode])', $theme);
    }
}
