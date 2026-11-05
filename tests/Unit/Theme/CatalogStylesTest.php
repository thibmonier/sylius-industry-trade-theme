<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Unit\Theme;

use PHPUnit\Framework\TestCase;

/**
 * Garde-fous des styles des pages du catalogue (US-223).
 */
final class CatalogStylesTest extends TestCase
{
    private const STYLES = __DIR__ . '/../../../assets/styles';

    public function testThemeImportsTheThreePageStylesheets(): void
    {
        $theme = (string) file_get_contents(self::STYLES . '/theme.scss');

        foreach (['pages/home', 'pages/product-list', 'pages/product-show'] as $page) {
            $this->assertStringContainsString("@use '" . $page . "';", $theme);
        }
    }

    public function testTokensAndComponentsAreScopedToTheActiveTheme(): void
    {
        $theme = (string) file_get_contents(self::STYLES . '/theme.scss');

        $this->assertStringNotContainsString(':root', $theme);
        $this->assertStringContainsString("[data-theme='industry-trade'] {", $theme);
        $this->assertDoesNotMatchRegularExpression(
            '/^(?:\.|:|body|html)[^\n]*\{/m',
            preg_replace('/\n\[data-theme=.*\z/s', '', $theme) ?? '',
            'une règle globale est déclarée hors du sélecteur du thème',
        );
    }

    public function testSuccessTokenOfTheThemeIsTheAAAGreenAndItsRgbTripletMatches(): void
    {
        $variables = (string) file_get_contents(self::STYLES . '/_variables.scss');
        $theme = (string) file_get_contents(self::STYLES . '/theme.scss');

        $this->assertMatchesRegularExpression('/\$industry-success: #0f5a36;/', $variables);
        $this->assertStringContainsString('$industry-success-rgb: 15, 90, 54;', $variables);
        $this->assertStringContainsString('--bs-success-rgb: #{$industry-success-rgb};', $theme);
    }

    public function testListFoldsIntoCardsBelow768PixelsWithoutHorizontalScroll(): void
    {
        $list = (string) file_get_contents(self::STYLES . '/pages/_product-list.scss');

        $this->assertStringContainsString('@media (max-width: 767.98px)', $list);
        $this->assertStringContainsString('display: block', $list);
        $this->assertStringContainsString('attr(data-label)', $list);
        $this->assertStringNotContainsString('overflow-x: scroll', $list);
    }

    public function testPagesStylesUseTokensOnly(): void
    {
        foreach (['_home', '_product-list', '_product-show'] as $file) {
            $this->assertDoesNotMatchRegularExpression(
                '/#[0-9a-fA-F]{3,8}\b/',
                (string) file_get_contents(self::STYLES . '/pages/' . $file . '.scss'),
                $file . ' contient une couleur en dur',
            );
        }
    }
}
