<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Unit\Theme;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ColorContrastTest extends TestCase
{
    /**
     * Test que tous les jetons de couleur respectent le niveau AAA de WCAG 2.1
     * (ratio >= 7:1 pour le texte, >= 3:1 pour les éléments d'interface).
     */
    #[DataProvider('colorPairsProvider')]
    public function testColorContrastMeetsWCAGAAA(string $foreground, string $background, float $minimumRatio): void
    {
        $ratio = $this->calculateContrastRatio($foreground, $background);

        $this->assertGreaterThanOrEqual(
            $minimumRatio,
            $ratio,
            sprintf(
                'La paire %s sur %s a un ratio de %.2f:1, inférieur au minimum AAA de %.1f:1',
                $foreground,
                $background,
                $ratio,
                $minimumRatio
            )
        );
    }

    /**
     * @return iterable<string, array{string, string, float}>
     */
    public static function colorPairsProvider(): iterable
    {
        // Format: [couleur avant-plan, couleur arrière-plan, ratio minimum requis]
        // 7:1 pour le texte normal (AAA), 3:1 pour les éléments d'interface

        // Paires de base
        yield 'navy sur blanc' => ['#1f3a5f', '#ffffff', 7.0];
        yield 'blanc sur navy' => ['#ffffff', '#1f3a5f', 7.0];
        yield 'accent sur blanc' => ['#8a3800', '#ffffff', 7.0];
        yield 'blanc sur accent' => ['#ffffff', '#8a3800', 7.0];
        yield 'accent sur surface' => ['#8a3800', '#f3f5f8', 7.0];
        yield 'blanc sur accent-dark' => ['#ffffff', '#6b2b00', 7.0];
        yield 'accent sur accent-subtle' => ['#8a3800', '#fbf1ea', 7.0];
        yield 'muted sur blanc' => ['#3f4753', '#ffffff', 7.0];
        yield 'muted sur surface' => ['#3f4753', '#f3f5f8', 7.0];
        yield 'success sur blanc' => ['#0f5a36', '#ffffff', 7.0];
        yield 'warning sur blanc' => ['#774000', '#ffffff', 7.0];
        yield 'blanc sur danger' => ['#ffffff', '#962130', 7.0];

        // Paires éditoriales
        yield 'ink sur paper' => ['#1b2533', '#faf6ef', 7.0];
        yield 'navy sur paper' => ['#1f3a5f', '#faf6ef', 7.0];
        yield 'accent sur paper' => ['#8a3800', '#faf6ef', 7.0];
        yield 'muted sur paper' => ['#3f4753', '#faf6ef', 7.0];
        yield 'navy sur sand' => ['#1f3a5f', '#efe4d2', 7.0];
        yield 'muted sur sand' => ['#3f4753', '#efe4d2', 7.0];
        yield 'sand-text sur sand' => ['#4a2a0e', '#efe4d2', 7.0];
        yield 'accent-on-sand sur sand' => ['#7a3200', '#efe4d2', 7.0];
        yield 'teal sur paper' => ['#0e5a5e', '#faf6ef', 7.0];
        yield 'blanc sur teal' => ['#ffffff', '#0e5a5e', 7.0];
        yield 'gold sur night' => ['#f2b544', '#141b26', 7.0];
        yield 'blanc sur night' => ['#ffffff', '#141b26', 7.0];
        yield 'night-text sur night' => ['#c9c3b8', '#141b26', 7.0];
        yield 'night-text sur night-raised' => ['#c9c3b8', '#1b2433', 7.0];

        // Éléments d'interface (ratio minimum 3:1)
        yield 'night-line sur night (UI)' => ['#8b96a8', '#141b26', 3.0];
    }

    private function calculateContrastRatio(string $foreground, string $background): float
    {
        $fgLuminance = $this->getRelativeLuminance($foreground);
        $bgLuminance = $this->getRelativeLuminance($background);

        $lighter = max($fgLuminance, $bgLuminance);
        $darker = min($fgLuminance, $bgLuminance);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    private function getRelativeLuminance(string $hex): float
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $r = $r <= 0.03928 ? $r / 12.92 : (($r + 0.055) / 1.055) ** 2.4;
        $g = $g <= 0.03928 ? $g / 12.92 : (($g + 0.055) / 1.055) ** 2.4;
        $b = $b <= 0.03928 ? $b / 12.92 : (($b + 0.055) / 1.055) ** 2.4;

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }
}
