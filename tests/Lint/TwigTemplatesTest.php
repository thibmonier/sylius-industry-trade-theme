<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Lint;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test de lint des gabarits Twig du thème.
 * Vérifie que tous les gabarits sont présents et syntaxiquement valides.
 */
final class TwigTemplatesTest extends TestCase
{
    private const string THEME_ROOT = __DIR__ . '/../..';

    /**
     * @param string $templatePath Chemin relatif depuis la racine du thème
     */
    #[DataProvider('requiredTemplatesProvider')]
    public function testTemplateExists(string $templatePath): void
    {
        $fullPath = self::THEME_ROOT . '/' . $templatePath;

        $this->assertFileExists($fullPath, sprintf('Le gabarit %s doit exister', $templatePath));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function requiredTemplatesProvider(): iterable
    {
        yield 'layout de base' => ['templates/bundles/SyliusShopBundle/shared/layout/base.html.twig'];
        yield 'en-tête' => ['templates/bundles/SyliusShopBundle/shared/layout/base/header/content.html.twig'];
        yield 'pied de page' => ['templates/bundles/SyliusShopBundle/shared/layout/base/footer/content.html.twig'];
        yield 'messages flash' => ['templates/bundles/SyliusShopBundle/shared/flashes.html.twig'];
        yield 'composant boutons' => ['templates/components/buttons.html.twig'];
        yield 'composant tableaux' => ['templates/components/tables.html.twig'];
        yield 'composant champs' => ['templates/components/forms.html.twig'];
    }
}
