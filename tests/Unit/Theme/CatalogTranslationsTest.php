<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Unit\Theme;

use PHPUnit\Framework\TestCase;

/**
 * Les traductions du catalogue existent en français et en anglais, avec les mêmes clés (US-223).
 */
final class CatalogTranslationsTest extends TestCase
{
    public function testFrenchAndEnglishDefineTheSameKeys(): void
    {
        $this->assertSame($this->keys('en'), $this->keys('fr'));
    }

    public function testNoTranslationIsEmpty(): void
    {
        foreach (['en', 'fr'] as $locale) {
            foreach ($this->keys($locale) as $key) {
                $this->assertNotSame('', $key);
            }
        }

        $this->assertNotEmpty($this->keys('fr'));
    }

    /**
     * @return list<string>
     */
    private function keys(string $locale): array
    {
        $content = (string) file_get_contents(__DIR__ . '/../../../translations/industry_trade.' . $locale . '.yaml');
        preg_match_all('/^\s+([a-z_]+): \'[^\']+\'$/m', $content, $matches);

        return $matches[1];
    }
}
