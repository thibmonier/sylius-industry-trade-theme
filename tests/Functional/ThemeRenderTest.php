<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Functional;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Repository\ChannelRepositoryInterface;
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
        $container = self::getContainer();

        // Récupérer le canal par défaut et activer le thème
        /** @var ChannelRepositoryInterface $channelRepository */
        $channelRepository = $container->get('sylius.repository.channel');
        /** @var ChannelInterface|null $channel */
        $channel = $channelRepository->findOneBy([]);

        $this->assertNotNull($channel, 'Un canal doit exister dans l\'application de test');

        $channel->setThemeName('industrie-negoce');

        $entityManager = $container->get('doctrine.orm.entity_manager');
        $entityManager->flush();

        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('[data-theme="industry-trade"]');
    }

    /**
     * Test que le thème inactif ne casse pas l'application.
     *
     * Scénario alternatif : le thème est installé mais non activé sur le canal,
     * l'application fonctionne avec les gabarits Sylius par défaut.
     */
    public function testThemeInactiveDoesNotBreak(): void
    {
        $client = self::createClient();
        $container = self::getContainer();

        // Récupérer le canal et désactiver le thème
        /** @var ChannelRepositoryInterface $channelRepository */
        $channelRepository = $container->get('sylius.repository.channel');
        /** @var ChannelInterface|null $channel */
        $channel = $channelRepository->findOneBy([]);

        $this->assertNotNull($channel, 'Un canal doit exister dans l\'application de test');

        $channel->setThemeName(null);

        $entityManager = $container->get('doctrine.orm.entity_manager');
        $entityManager->flush();

        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('[data-theme="industry-trade"]');
    }
}
