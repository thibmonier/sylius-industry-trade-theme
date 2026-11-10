<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Functional;

use Symfony\Component\Process\Process;

/**
 * US-226 : le commutateur clair/sombre, monté dans l'application hôte de test (A-50-2) avec le thème actif sur le canal.
 *
 * Le script du <head> est extrait de la page rendue puis exécuté sous Node avec un DOM minimal : on teste le script livré,
 * pas une copie.
 */
final class ColorModeToggleTest extends CatalogWebTestCase
{
    private const HARNESS = __DIR__ . '/ColorMode/harness.js';

    public function testScriptSetsTheModeInTheHeadBeforeStylesheetsAndBody(): void
    {
        $this->client->request('GET', '/' . self::LOCALE . '/');

        $this->assertResponseIsSuccessful();
        $html = (string) $this->client->getResponse()
            ->getContent();
        $script = (int) strpos($html, 'data-industry-color-mode');
        $this->assertGreaterThan(0, $script, 'le script du mode sombre est absent de la page');
        $this->assertLessThan((int) strpos($html, '</head>'), $script, 'le script doit être dans le <head>');
        $this->assertSelectorExists('head script[data-industry-color-mode]');
        $firstStylesheet = strpos($html, 'rel="stylesheet"');
        if ($firstStylesheet !== false) {
            $this->assertLessThan(
                $firstStylesheet,
                $script,
                'le script doit précéder les feuilles de style (pas de scintillement)',
            );
        }
    }

    public function testToggleIsAnAccessibleButtonInTheTopBarHiddenUntilTheScriptRuns(): void
    {
        $this->client->request('GET', '/' . self::LOCALE . '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists(
            'header button[type="button"][data-industry-color-mode-toggle][aria-pressed="false"][hidden]',
        );
        $this->assertSelectorTextContains('header [data-industry-color-mode-toggle]', 'Dark mode');
    }

    public function testHookablesOfTheApplicationInTheHeaderAreKept(): void
    {
        $this->client->request('GET', '/' . self::LOCALE . '/');

        $this->assertSelectorTextSame('header [data-host-app="header"]', 'host app');
    }

    public function testNothingIsAddedOnAChannelWithoutTheTheme(): void
    {
        $this->channel->setThemeName(null);
        $this->em->flush();

        $this->client->request('GET', '/' . self::LOCALE . '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('[data-industry-color-mode-toggle]');
        $this->assertSelectorNotExists('script[data-industry-color-mode]');
    }

    public function testSystemInDarkModeLeavesTheStylesheetToApplyTheDarkTokensAndMarksTheToggleAsPressed(): void
    {
        $result = $this->runScript([
            'systemDark' => true,
        ]);

        $this->assertNull($result['mode'], 'sans choix, aucun attribut : la feuille de style suit le système');
        $this->assertSame('true', $result['pressed']);
        $this->assertFalse($result['hidden']);
    }

    public function testSystemInLightModeLeavesTheToggleUnpressed(): void
    {
        $result = $this->runScript([
            'systemDark' => false,
        ]);

        $this->assertNull($result['mode']);
        $this->assertSame('false', $result['pressed']);
    }

    public function testStoredChoiceIsAppliedBeforeTheFirstRender(): void
    {
        $result = $this->runScript([
            'stored' => 'dark',
            'systemDark' => false,
        ]);

        $this->assertSame(
            'dark',
            $result['modeAtScriptEnd'],
            'le mode doit être posé dès l\'exécution du script du <head>',
        );
        $this->assertSame('true', $result['pressed']);
    }

    public function testChoosingDarkIsKeptForTheNextVisit(): void
    {
        $result = $this->runScript([
            'systemDark' => false,
            'click' => 1,
        ]);

        $this->assertSame('dark', $result['mode']);
        $this->assertSame('dark', $result['storage']);
        $this->assertSame('true', $result['pressed']);
    }

    public function testChoosingLightAgainAppliesTheLightTheme(): void
    {
        $result = $this->runScript([
            'stored' => 'dark',
            'systemDark' => true,
            'click' => 1,
        ]);

        $this->assertSame('light', $result['mode']);
        $this->assertSame('light', $result['storage']);
        $this->assertSame('false', $result['pressed']);
    }

    public function testExplicitLightChoiceBeatsTheSystemDarkSetting(): void
    {
        $result = $this->runScript([
            'stored' => 'light',
            'systemDark' => true,
        ]);

        $this->assertSame('light', $result['modeAtScriptEnd']);
        $this->assertSame('false', $result['pressed']);
    }

    public function testToggleStillWorksWhenLocalStorageIsUnavailable(): void
    {
        $result = $this->runScript([
            'systemDark' => false,
            'storageBroken' => true,
            'click' => 1,
        ]);

        $this->assertNull($result['error'], 'aucune erreur JavaScript attendue');
        $this->assertSame('dark', $result['mode'], 'le mode s\'applique à la page même sans stockage');
        $this->assertSame('true', $result['pressed']);
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

    public function testUnknownStoredValueIsIgnored(): void
    {
        $result = $this->runScript([
            'stored' => 'blue',
            'systemDark' => false,
        ]);

        $this->assertNull($result['mode']);
    }

    /**
     * @param array<string, mixed> $scenario
     *
     * @return array{mode: ?string, modeAtScriptEnd: ?string, pressed: ?string, hidden: bool, storage: ?string, error: ?string}
     */
    private function runScript(array $scenario): array
    {
        $node = (new \Symfony\Component\Process\ExecutableFinder())->find('node');
        if ($node === null) {
            $this->markTestSkipped('Node.js est requis pour exécuter le script du mode sombre.');
        }

        $this->client->request('GET', '/' . self::LOCALE . '/');
        $html = (string) $this->client->getResponse()
            ->getContent();
        $this->assertSame(1, preg_match('#<script data-industry-color-mode[^>]*>(.*?)</script>#s', $html, $match));

        $process = new Process([$node, self::HARNESS], null, null, json_encode([
            'script' => $match[1] ?? '',
        ] + $scenario, \JSON_THROW_ON_ERROR));
        $process->mustRun();

        /** @var array{mode: ?string, modeAtScriptEnd: ?string, pressed: ?string, hidden: bool, storage: ?string, error: ?string} $result */
        $result = json_decode($process->getOutput(), true, 512, \JSON_THROW_ON_ERROR);

        return $result;
    }
}
