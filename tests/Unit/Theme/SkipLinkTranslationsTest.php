<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Unit\Theme;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Le lien d'évitement de templates/bundles/SyliusShopBundle/shared/layout/base.html.twig
 * demande `skip_link` au domaine `shop_layout`, que le cœur de Sylius ne traduit pas (US-280).
 */
final class SkipLinkTranslationsTest extends TestCase
{
    #[DataProvider('labelsProvider')]
    public function testSkipLinkIsTranslated(string $locale, string $expected): void
    {
        $messages = Yaml::parseFile(__DIR__ . '/../../../translations/shop_layout.' . $locale . '.yaml');

        $this->assertIsArray($messages);
        $this->assertSame($expected, $messages['skip_link'] ?? null);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function labelsProvider(): iterable
    {
        yield 'anglais' => ['en', 'Skip to main content'];
        yield 'français' => ['fr', 'Aller au contenu principal'];
    }
}
