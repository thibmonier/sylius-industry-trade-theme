<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Unit\TemplateDrift;

use PHPUnit\Framework\TestCase;
use Tmonier\SyliusIndustryTradeTheme\Tests\TemplateDrift\TemplateDriftDetector;

final class TemplateDriftDetectorTest extends TestCase
{
    private const KEY = 'SyliusShopBundle/shared/flashes.html.twig';

    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/theme-drift-' . bin2hex(random_bytes(6));
        $this->write('theme/templates/bundles/' . self::KEY, '{# override #}');
        $this->write(
            'vendor/sylius/sylius/src/Sylius/Bundle/ShopBundle/templates/shared/flashes.html.twig',
            "<div>origin</div>\n",
        );
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testNoDriftWhenTheOriginalIsUnchanged(): void
    {
        $recorded = [
            self::KEY => TemplateDriftDetector::fingerprint("<div>origin</div>\n"),
        ];

        self::assertSame([], $this->detector()->check($recorded));
    }

    public function testNoDriftIgnoresLineEndingsAndTrailingWhitespace(): void
    {
        $recorded = [
            self::KEY => TemplateDriftDetector::fingerprint("<div>origin</div>   \r\n\r\n"),
        ];

        self::assertSame([], $this->detector()->check($recorded));
    }

    public function testAModifiedOriginalFailsAndNamesTheTemplate(): void
    {
        $recorded = [
            self::KEY => TemplateDriftDetector::fingerprint('<div>old</div>'),
        ];

        $errors = $this->detector()
            ->check($recorded);

        self::assertCount(1, $errors);
        self::assertStringContainsString(self::KEY, $errors[0]);
        self::assertStringContainsString('modifié', $errors[0]);
    }

    public function testADeletedOriginalFailsWithTheDeletedTemplateMessage(): void
    {
        unlink($this->root . '/vendor/sylius/sylius/src/Sylius/Bundle/ShopBundle/templates/shared/flashes.html.twig');
        $recorded = [
            self::KEY => TemplateDriftDetector::fingerprint('<div>origin</div>'),
        ];

        $errors = $this->detector()
            ->check($recorded);

        self::assertCount(1, $errors);
        self::assertStringContainsString('gabarit supprimé', $errors[0]);
        self::assertStringContainsString(self::KEY, $errors[0]);
    }

    public function testAnOverrideMissingFromTheFingerprintsFailsAskingToRegisterIt(): void
    {
        $errors = $this->detector()
            ->check([]);

        self::assertCount(1, $errors);
        self::assertStringContainsString(self::KEY, $errors[0]);
        self::assertStringContainsString('bin/template-fingerprints --update', $errors[0]);
    }

    public function testComputeThenCheckIsGreenAfterTheReviewedUpdate(): void
    {
        $this->write(
            'vendor/sylius/sylius/src/Sylius/Bundle/ShopBundle/templates/shared/flashes.html.twig',
            '<div>changed upstream</div>',
        );

        $fingerprints = $this->detector()
            ->compute();

        self::assertSame([], $this->detector()->check($fingerprints));
    }

    public function testOverridesAreDiscoveredAutomaticallyInEveryBundleDirectory(): void
    {
        $this->write('theme/templates/bundles/SyliusAdminBundle/x/y.html.twig', '');
        $this->write('theme/templates/shop/home.html.twig', '');

        self::assertSame(['SyliusAdminBundle/x/y.html.twig', self::KEY], $this->detector() ->overrides());
    }

    private function detector(): TemplateDriftDetector
    {
        return new TemplateDriftDetector($this->root . '/theme', $this->root . '/vendor');
    }

    private function write(string $relative, string $content): void
    {
        $path = $this->root . '/' . $relative;
        if (! is_dir(\dirname($path))) {
            mkdir(\dirname($path), 0o777, true);
        }
        file_put_contents($path, $content);
    }

    private function remove(string $path): void
    {
        if (is_file($path)) {
            unlink($path);

            return;
        }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $child) {
            $this->remove($path . '/' . $child);
        }
        if (is_dir($path)) {
            rmdir($path);
        }
    }
}
