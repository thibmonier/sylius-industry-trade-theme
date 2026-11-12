<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Unit\Theme;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * US-227 : le script de saisie rapide sous une CSP stricte, comme celui du mode d'affichage (US-279) : nonce de l'application
 * quand elle en fournit un, sinon empreinte SHA-256 publiée dans les deux README et vérifiée ici.
 */
final class QuickFindScriptCspTest extends TestCase
{
    private const TEMPLATE = __DIR__ . '/../../../templates/industry_trade/_quick_find_script.html.twig';

    public function testScriptCarriesTheNonceOfTheApplicationWhenThereIsOne(): void
    {
        $html = $this->render([
            'industry_trade_script_nonce' => 'abc123',
        ]);

        $this->assertMatchesRegularExpression('#<script data-industry-quick-find-script nonce="abc123">#', $html);
    }

    public function testScriptHasNoNonceAttributeWithoutAnApplicationNonce(): void
    {
        $html = $this->render([]);

        $this->assertStringContainsString('<script data-industry-quick-find-script>', $html);
        $this->assertStringNotContainsString('nonce=', $html);
    }

    public function testScriptContentIsStaticSoItsHashDoesNotDependOnTheRendering(): void
    {
        $this->assertSame(
            $this->content($this->render([])),
            $this->content($this->render([
                'industry_trade_script_nonce' => 'abc123',
            ])),
        );
    }

    #[DataProvider('readmesProvider')]
    public function testReadmePublishesTheCurrentFingerprintOfTheScript(string $readme): void
    {
        $fingerprint = $this->fingerprint();

        $this->assertStringContainsString(
            "'" . $fingerprint . "'",
            (string) file_get_contents(__DIR__ . '/../../../' . $readme),
            sprintf(
                'L\'empreinte du script de saisie rapide dans %s ne correspond plus au script : remplacez-la par %s.',
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
