<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\TemplateDrift;

/**
 * Compares the Sylius templates overridden by the theme (templates/bundles/<Bundle>/**)
 * with the fingerprints recorded at the last review. Plain PHP: used by the tests and by bin/template-fingerprints.
 */
final class TemplateDriftDetector
{
    private const UPDATE_COMMAND = 'bin/template-fingerprints --update';

    public function __construct(
        private readonly string $themeRoot,
        private readonly string $vendorRoot,
    ) {}

    public static function fingerprint(string $content): string
    {
        $lines = preg_split('/\R/', $content) ?: [];
        $normalized = rtrim(implode("\n", array_map(rtrim(...), $lines)));

        return hash('sha256', $normalized . "\n");
    }

    public static function minorVersion(string $version): string
    {
        preg_match('/(\d+)\.(\d+)/', $version, $matches);

        return isset($matches[2]) ? $matches[1] . '.' . $matches[2] : $version;
    }

    /**
     * @return array<string, array<string, string>> fingerprints per Sylius minor version
     */
    public static function loadFingerprints(string $file): array
    {
        if (! is_file($file)) {
            return [];
        }
        /** @var mixed $decoded */
        $decoded = json_decode((string) file_get_contents($file), true, 512, \JSON_THROW_ON_ERROR);

        /** @var array<string, array<string, string>> $fingerprints */
        $fingerprints = \is_array($decoded) ? $decoded : [];

        return $fingerprints;
    }

    /**
     * @return list<string> overrides as "<Bundle>/<path>", sorted
     */
    public function overrides(): array
    {
        $base = $this->themeRoot . '/templates/bundles';
        if (! is_dir($base)) {
            return [];
        }
        $keys = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile()) {
                $keys[] = str_replace('\\', '/', substr($file->getPathname(), \strlen($base) + 1));
            }
        }
        sort($keys);

        return $keys;
    }

    /**
     * @return array<string, string> fingerprint of the original of every override that still exists upstream
     */
    public function compute(): array
    {
        $fingerprints = [];
        foreach ($this->overrides() as $key) {
            $path = $this->originalPath($key);
            if (is_file($path)) {
                $fingerprints[$key] = self::fingerprint((string) file_get_contents($path));
            }
        }

        return $fingerprints;
    }

    /**
     * @param array<string, string> $recorded fingerprints of the installed Sylius minor version
     *
     * @return list<string> one message per problem, empty when there is no drift
     */
    public function check(array $recorded): array
    {
        $errors = [];
        foreach ($this->overrides() as $key) {
            $error = $this->checkOne($key, $recorded[$key] ?? null);
            if ($error !== null) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    private function checkOne(string $key, ?string $expected): ?string
    {
        $path = $this->originalPath($key);
        if (! is_file($path)) {
            return sprintf(
                '%s : gabarit supprimé en amont (%s), adapter ou retirer la surcharge.',
                $key,
                $this->relative($path),
            );
        }
        if ($expected === null) {
            return sprintf(
                '%s : surcharge absente du fichier d\'empreintes, l\'enregistrer avec %s après revue.',
                $key,
                self::UPDATE_COMMAND,
            );
        }
        if (self::fingerprint((string) file_get_contents($path)) !== $expected) {
            return sprintf(
                '%s : gabarit d\'origine modifié (%s), relire le diff amont puis adapter la surcharge.',
                $key,
                $this->relative($path),
            );
        }

        return null;
    }

    private function originalPath(string $key): string
    {
        [$bundle, $template] = explode('/', $key, 2) + [
            1 => '',
        ];
        $directory = str_starts_with($bundle, 'Sylius') ? substr($bundle, \strlen('Sylius')) : $bundle;

        return sprintf('%s/sylius/sylius/src/Sylius/Bundle/%s/templates/%s', $this->vendorRoot, $directory, $template);
    }

    private function relative(string $path): string
    {
        return str_starts_with($path, $this->vendorRoot . '/') ? 'vendor/' . substr(
            $path,
            \strlen($this->vendorRoot) + 1,
        ) : $path;
    }
}
