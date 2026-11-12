<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Unit\Theme;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * US-226, US-278 : le script du <head> qui applique et mémorise le mode d'affichage (automatique, clair, sombre).
 *
 * Le gabarit livré est rendu par Twig puis son script est exécuté sous Node avec un DOM minimal (harness.js) : on teste le
 * script du thème, pas une copie.
 */
final class ColorModeScriptTest extends TestCase
{
    private const HARNESS = __DIR__ . '/../../Functional/ColorMode/harness.js';

    public function testSystemInDarkModeLeavesTheStylesheetToApplyTheDarkTokensWithAutomaticPressed(): void
    {
        $result = $this->runScript([
            'systemDark' => true,
        ]);

        $this->assertNull($result['mode'], 'sans choix, aucun attribut : la feuille de style suit le système');
        $this->assertSame([
            'auto' => 'true',
            'light' => 'false',
            'dark' => 'false',
        ], $result['pressed']);
        $this->assertFalse($result['group']['hidden']);
    }

    public function testStoredChoiceIsAppliedBeforeTheFirstRender(): void
    {
        $result = $this->runScript([
            'stored' => 'dark',
            'systemDark' => false,
        ]);

        $this->assertSame('dark', $result['modeAtScriptEnd']);
        $this->assertSame('true', $result['pressed']['dark']);
        $this->assertSame('false', $result['pressed']['auto']);
    }

    public function testExplicitLightChoiceBeatsTheSystemDarkSetting(): void
    {
        $result = $this->runScript([
            'stored' => 'light',
            'systemDark' => true,
        ]);

        $this->assertSame('light', $result['modeAtScriptEnd']);
        $this->assertSame('true', $result['pressed']['light']);
    }

    public function testChoosingDarkIsKeptForTheNextVisit(): void
    {
        $result = $this->runScript([
            'systemDark' => false,
            'steps' => ['dark'],
        ]);

        $this->assertSame('dark', $result['mode']);
        $this->assertSame('dark', $result['storage']);
        $this->assertSame('true', $result['pressed']['dark']);
    }

    public function testChoosingLightOnADarkSystemAppliesTheLightTheme(): void
    {
        $result = $this->runScript([
            'systemDark' => true,
            'steps' => ['light'],
        ]);

        $this->assertSame('light', $result['mode']);
        $this->assertSame('light', $result['storage']);
    }

    public function testChoosingAutomaticRemovesTheStoredChoiceAndFollowsTheSystem(): void
    {
        $result = $this->runScript([
            'stored' => 'dark',
            'systemDark' => false,
            'steps' => ['auto'],
        ]);

        $this->assertFalse($result['storageKeyPresent'], 'la clé mémorisée doit être retirée');
        $this->assertNull($result['mode'], 'sans attribut, la page suit le système (clair ici)');
        $this->assertSame('true', $result['pressed']['auto']);
        $this->assertSame('false', $result['pressed']['dark']);
    }

    public function testAutomaticModeFollowsTheSystemChangingDuringTheVisit(): void
    {
        $result = $this->runScript([
            'systemDark' => false,
            'steps' => ['system-dark'],
        ]);

        $this->assertNull($result['mode']);
        $this->assertContains(
            [
                'type' => 'industry-trade:color-mode-change',
                'detail' => [
                    'choice' => 'auto',
                    'mode' => 'dark',
                ],
            ],
            $result['events'],
        );
    }

    public function testAnExplicitChoiceIsNotAnnouncedAgainWhenTheSystemChanges(): void
    {
        $result = $this->runScript([
            'stored' => 'light',
            'systemDark' => false,
            'steps' => ['system-dark'],
        ]);

        $this->assertSame([], $result['events']);
    }

    public function testChoosingAutomaticWhenLocalStorageIsUnavailableFollowsTheSystemWithoutError(): void
    {
        $result = $this->runScript([
            'systemDark' => true,
            'storageBroken' => true,
            'steps' => ['dark', 'auto'],
        ]);

        $this->assertNull($result['error'], 'aucune erreur JavaScript attendue');
        $this->assertNull($result['mode']);
        $this->assertSame('true', $result['pressed']['auto']);
    }

    public function testChoiceStillAppliesWhenLocalStorageIsUnavailable(): void
    {
        $result = $this->runScript([
            'systemDark' => false,
            'storageBroken' => true,
            'steps' => ['dark'],
        ]);

        $this->assertNull($result['error']);
        $this->assertSame('dark', $result['mode'], 'le mode s\'applique à la page même sans stockage');
        $this->assertSame('true', $result['pressed']['dark']);
    }

    public function testScriptDoesNotFailWhenLocalStorageIsUnavailableAtLoad(): void
    {
        $result = $this->runScript([
            'systemDark' => true,
            'storageBroken' => true,
        ]);

        $this->assertNull($result['error']);
        $this->assertNull($result['mode']);
    }

    public function testUnknownStoredValueIsIgnoredAndThePageFollowsTheSystem(): void
    {
        $result = $this->runScript([
            'stored' => 'blue',
            'systemDark' => false,
        ]);

        $this->assertNull($result['mode']);
        $this->assertSame('true', $result['pressed']['auto']);
    }

    /**
     * @param array<string, mixed> $scenario
     *
     * @return array{mode: ?string, modeAtScriptEnd: ?string, group: array{hidden: bool}, pressed: array<string, string>, storage: ?string, storageKeyPresent: bool, events: list<array{type: string, detail: array<string, string>}>, error: ?string}
     */
    private function runScript(array $scenario): array
    {
        $node = (new ExecutableFinder())->find('node');
        if ($node === null) {
            $this->markTestSkipped('Node.js est requis pour exécuter le script du mode d\'affichage.');
        }

        $twig = new Environment(new FilesystemLoader(__DIR__ . '/../../../templates/industry_trade'));
        $html = $twig->render('_color_mode_script.html.twig');
        if (preg_match('#<script[^>]*data-industry-color-mode[^>]*>(.*?)</script>#s', $html, $match) !== 1) {
            self::fail('le script du mode d\'affichage est absent du gabarit rendu');
        }

        $process = new Process([$node, self::HARNESS], null, null, json_encode([
            'script' => $match[1],
        ] + $scenario, \JSON_THROW_ON_ERROR));
        $process->mustRun();

        /** @var array{mode: ?string, modeAtScriptEnd: ?string, group: array{hidden: bool}, pressed: array<string, string>, storage: ?string, storageKeyPresent: bool, events: list<array{type: string, detail: array<string, string>}>, error: ?string} $result */
        $result = json_decode($process->getOutput(), true, 512, \JSON_THROW_ON_ERROR);

        return $result;
    }
}
