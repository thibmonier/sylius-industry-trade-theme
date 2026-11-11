<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Functional;

/**
 * US-226 : le commutateur clair/sombre, monté dans l'application hôte de test (A-50-2) avec le thème actif sur le canal.
 *
 * Le comportement du script (choix, retour à l'automatique, stockage refusé) est testé par
 * Tests\Unit\Theme\ColorModeScriptTest ; ici, le balisage rendu dans la page.
 */
final class ColorModeToggleTest extends CatalogWebTestCase
{
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

    public function testSwitchIsAGroupOfThreeNamedButtonsInTheTopBarHiddenUntilTheScriptRuns(): void
    {
        $this->client->request('GET', '/' . self::LOCALE . '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('header [role="group"][data-industry-color-mode-toggle][hidden]');
        foreach (['auto' => 'Automatic', 'light' => 'Light', 'dark' => 'Dark'] as $choice => $label) {
            $selector = sprintf('header [data-industry-color-mode-toggle] button[type="button"][data-industry-color-mode-choice="%s"][aria-pressed="false"]', $choice);
            $this->assertSelectorTextContains($selector, $label);
        }
        $this->assertSelectorExists('header [data-industry-color-mode-toggle][aria-label="Display mode"]');
    }

    public function testSwitchSitsInAContentWidthColumnSoItDoesNotStretchAcrossTheTopBar(): void
    {
        $this->client->request('GET', '/' . self::LOCALE . '/');

        $this->assertSelectorExists('header .row > .col-12.col-md-auto > [data-industry-color-mode-toggle]');
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
}
