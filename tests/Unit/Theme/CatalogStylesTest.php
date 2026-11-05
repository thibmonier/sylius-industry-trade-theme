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
