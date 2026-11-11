<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Unit\Theme;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * US-279 : le script du mode d'affichage sous une CSP stricte. Un nonce de l'application est repris quand elle en fournit un ;
 * sinon l'intégrateur autorise l'empreinte SHA-256 du script, publiée dans les deux README et vérifiée ici.
 */
final class ColorModeScriptCspTest extends TestCase
{
    private const TEMPLATE = __DIR__ . '/../../../templates/industry_trade/_color_mode_script.html.twig';

    public function testScriptCarriesTheNonceOfTheApplicationWhenThereIsOne(): void
    {
        $html = $this->render([
            'industry_trade_script_nonce' => 'abc123',
        ]);

        $this->assertMatchesRegularExpression('#<script data-industry-color-mode nonce="abc123">#', $html);
    }

    public function testNonceIsEscapedInTheAttribute(): void
    {
        $html = $this->render([
            'industry_trade_script_nonce' => 'a"><b',
        ]);

        $this->assertStringContainsString('nonce="a&quot;&gt;&lt;b"', $html);
    }

    public function testScriptHasNoNonceAttributeWithoutAnApplicationNonce(): void
    {
        $html = $this->render([]);

        $this->assertStringContainsString('<script data-industry-color-mode>', $html);
        $this->assertStringNotContainsString('nonce', $html);
    }

    public function testScriptContentIsStaticSoItsHashDoesNotDependOnTheRendering(): void
    {
        $this->assertSame(
            $this->content($this->render([])),
            $this->content($this->render([
                'industry_trade_script_nonce' => 'abc123',
            ])),
        );
        $this->assertDoesNotMatchRegularExpression('/\{\{|\{%/', $this->content($this->render([])));
    }

    #[DataProvider('readmesProvider')]
    public function testReadmePublishesTheCurrentFingerprintOfTheScript(string $readme): void
    {
        $fingerprint = $this->fingerprint();

        $this->assertStringContainsString(
            "'" . $fingerprint . "'",
            (string) file_get_contents(__DIR__ . '/../../../' . $readme),
            sprintf(
                'L\'empreinte du script du mode d\'affichage dans %s ne correspond plus au script : remplacez-la par %s.',
                $readme,
                $fingerprint,
            ),
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function readmesProvider(): iterable
    {
        yield 'README anglais' => ['README.md'];
        yield 'README français' => ['README.fr.md'];
    }

    private function fingerprint(): string
    {
        return 'sha256-' . base64_encode(hash('sha256', $this->content($this->render([])), true));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function render(array $context): string
    {
        $twig = new Environment(new FilesystemLoader(dirname(self::TEMPLATE)));

        return $twig->render(basename(self::TEMPLATE), $context);
    }

    private function content(string $html): string
    {
        if (preg_match('#<script[^>]*>(.*?)</script>#s', $html, $match) !== 1) {
            self::fail('aucune balise <script> dans le gabarit rendu');
        }

        return $match[1];
    }
}
