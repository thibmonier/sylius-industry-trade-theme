<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Unit\Theme;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Le fichier copié dans config/packages/ de l'application importe chaque fichier de hooks que le README fait copier.
 *
 * L'application de test importe ces fichiers elle-même : sans ce test, un hook non importé passe la CI fonctionnelle
 * mais n'est jamais enregistré dans une vraie installation (commutateur de la v0.2.0 absent).
 */
final class PackageConfigTest extends TestCase
{
    private const PACKAGE = __DIR__ . '/../../../config/packages/tmonier_sylius_industry_trade_theme.yaml';

    #[DataProvider('hookFilesProvider')]
    public function testPackageConfigImportsTheHookFile(string $file): void
    {
        /** @var array{imports?: list<array{resource?: string}>} $config */
        $config = Yaml::parseFile(self::PACKAGE);
        $resources = array_map(
            static fn(array $import): ?string => $import['resource'] ?? null,
            $config['imports'] ?? [],
        );

        $this->assertContains(
            '../twig_hooks/' . $file,
            $resources,
            sprintf('config/twig_hooks/%s n\'est pas importé par le fichier du paquet', $file),
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function hookFilesProvider(): iterable
    {
        foreach (glob(__DIR__ . '/../../../config/twig_hooks/*.yaml') ?: [] as $path) {
            yield basename($path) => [basename($path)];
        }
    }
}
