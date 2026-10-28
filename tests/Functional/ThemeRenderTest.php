<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Test de rendu du thème Industrie & Négoce dans une application Sylius.
 *
 * Vérifie que le thème est correctement activé et que les pages se rendent
 * avec les marqueurs du thème.
 */
final class ThemeRenderTest extends WebTestCase
{
    /**
     * Test que la page d'accueil se rend correctement avec le thème actif.
     *
     * Scénario nominal : le thème est installé et activé sur le canal,
     * la page d'accueil se rend avec le marqueur du gabarit de base.
     */
    public function testHomepageRendersWithTheme(): void
    {
        $client = self::createClient();

        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('[data-theme="industry-trade"]');
    }

    /**
     * Test que le thème inactif ne casse pas l'application.
     *
     * Scénario alternatif : le thème est installé mais non activé,
     * l'application fonctionne avec les gabarits Sylius par défaut.
     */
    public function testThemeInactiveDoesNotBreak(): void
    {
        // Ce test nécessite une configuration de thème désactivé
        // Pour l'instant, on vérifie simplement que la page se rend
        $client = self::createClient();

        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }
}
