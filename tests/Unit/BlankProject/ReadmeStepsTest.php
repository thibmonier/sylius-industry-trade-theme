<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Unit\BlankProject;

use PHPUnit\Framework\TestCase;
use Tmonier\SyliusIndustryTradeTheme\Tests\BlankProject\ReadmeSteps;

final class ReadmeStepsTest extends TestCase
{
    private const EXPECTED_STEPS = ['require', 'copy-config', 'styles', 'styles-entry', 'build', 'theme-source'];

    public function testTheEnglishReadmeDeclaresTheInstallationSteps(): void
    {
        $this->assertSame(self::EXPECTED_STEPS, array_column($this->steps('README.md'), 'name'));
    }

    public function testTheFrenchReadmeRunsTheSameCommandsAsTheEnglishOne(): void
    {
        $this->assertSame($this->executable('README.md'), $this->executable('README.fr.md'));
    }

    public function testTheConfigurationCopyStepCopiesBothConfigurationItems(): void
    {
        $copy = $this->step('copy-config')['body'];

        $this->assertStringContainsString(
            'config/packages/tmonier_sylius_industry_trade_theme.yaml config/packages/',
            $copy,
        );
        $this->assertStringContainsString('config/twig_hooks config/', $copy);
    }

    public function testAFileStepNamesItsTargetAndWriteMode(): void
    {
        $source = $this->step('theme-source');

        $this->assertSame('config/packages/sylius_theme.yaml', $source['file']);
        $this->assertSame('replace', $source['write']);
        $this->assertStringContainsString("'%kernel.project_dir%/vendor/tmonier'", $source['body']);
    }

    public function testAMarkerWithoutStepNameIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('README marker without step name');

        ReadmeSteps::parse("<!-- blank-project: file=a.txt -->\n```bash\necho\n```");
    }

    public function testAnUnknownWriteModeIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('write must be append or replace');

        ReadmeSteps::parse("<!-- blank-project: step=x file=a.txt write=prepend -->\n```txt\nx\n```");
    }

    public function testCommentLinesAreNotExecutableContent(): void
    {
        $this->assertSame(
            "@use 'theme';",
            ReadmeSteps::executableBody("// assets/shop/entrypoint.scss\n@use 'theme';"),
        );
    }

    /**
     * @return array{name: string, lang: string, body: string, file: ?string, write: ?string}
     */
    private function step(string $name): array
    {
        foreach ($this->steps('README.md') as $step) {
            if ($name === $step['name']) {
                return $step;
            }
        }

        $this->fail(sprintf('README step "%s" not found.', $name));
    }

    /**
     * @return list<array{name: string, lang: string, body: string, file: ?string, write: ?string}>
     */
    private function steps(string $readme): array
    {
        return ReadmeSteps::parse((string) file_get_contents(\dirname(__DIR__, 3) . '/' . $readme));
    }

    /**
     * @return list<array{string, ?string, ?string, string}>
     */
    private function executable(string $readme): array
    {
        return array_map(
            static fn(array $step): array => [
                $step['name'],
                $step['file'],
                $step['write'],
                ReadmeSteps::executableBody($step['body']),
            ],
            $this->steps($readme),
        );
    }
}
