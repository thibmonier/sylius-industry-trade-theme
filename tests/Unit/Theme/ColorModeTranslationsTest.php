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
    public function testToggleLabelIsTranslated(string $locale, string $expected): void
    {
        /** @var array{industry_theme?: array{color_mode?: array{toggle?: string}}} $messages */
        $messages = Yaml::parseFile(__DIR__ . '/../../../translations/industry_theme.' . $locale . '.yaml');

        $this->assertSame($expected, $messages['industry_theme']['color_mode']['toggle'] ?? null);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function labelsProvider(): iterable
    {
        yield 'anglais' => ['en', 'Dark mode'];
        yield 'français' => ['fr', 'Mode sombre'];
    }
}
