<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Unit\TemplateDrift;

use Composer\InstalledVersions;
use PHPUnit\Framework\TestCase;
use Tmonier\SyliusIndustryTradeTheme\Tests\TemplateDrift\TemplateDriftDetector;

/**
 * Compares the Sylius templates the theme overrides with the fingerprints recorded at the last review.
 * On failure, see "Reviewing template drift" in the README.
 */
final class ThemeTemplateDriftTest extends TestCase
{
    public function testTheSyliusTemplatesOverriddenByTheThemeHaveNotDrifted(): void
    {
        $root = \dirname(__DIR__, 3);
        $minor = TemplateDriftDetector::minorVersion((string) InstalledVersions::getPrettyVersion('sylius/sylius'));
        $recorded = TemplateDriftDetector::loadFingerprints($root . '/tests/TemplateDrift/fingerprints.json');

        if (! isset($recorded[$minor])) {
            self::fail(
                sprintf(
                    'Aucune empreinte enregistrée pour Sylius %s : lancer bin/template-fingerprints --update après revue.',
                    $minor,
                ),
            );
        }

        $errors = (new TemplateDriftDetector($root, $root . '/vendor'))->check($recorded[$minor]);

        self::assertSame([], $errors, "Dérive des gabarits Sylius :\n" . implode("\n", $errors));
    }
}
