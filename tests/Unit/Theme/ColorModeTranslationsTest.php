<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Unit\Theme;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Le nom accessible du commutateur clair/sombre existe en anglais et en français (US-226).
 */
final class ColorModeTranslationsTest extends TestCase
{
    #[DataProvider('labelsProvider')]
    public function testLabelIsTranslated(string $locale, string $key, string $expected): void
    {
        /** @var array{industry_theme?: array{color_mode?: array<string, string>}} $messages */
        $messages = Yaml::parseFile(__DIR__ . '/../../../translations/industry_theme.' . $locale . '.yaml');

        $this->assertSame($expected, $messages['industry_theme']['color_mode'][$key] ?? null);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function labelsProvider(): iterable
    {
        yield 'anglais, groupe' => ['en', 'group', 'Display mode'];
        yield 'anglais, automatique' => ['en', 'auto', 'Automatic'];
        yield 'anglais, clair' => ['en', 'light', 'Light'];
        yield 'anglais, sombre' => ['en', 'dark', 'Dark'];
        yield 'français, groupe' => ['fr', 'group', 'Mode d\'affichage'];
        yield 'français, automatique' => ['fr', 'auto', 'Automatique'];
        yield 'français, clair' => ['fr', 'light', 'Clair'];
        yield 'français, sombre' => ['fr', 'dark', 'Sombre'];
    }
}
