<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\ChannelInterface;
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
        $channelRepository = $container->get('sylius.repository.channel');
        assert(method_exists($channelRepository, 'findOneBy'));
        /** @var ChannelInterface|null $channel */
        $channel = $channelRepository->findOneBy([]);

        $this->assertNotNull($channel, 'Un canal doit exister dans l\'application de test');

        $channel->setThemeName('industrie-negoce');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $entityManager->flush();

        $client->request('GET', '/en_US/');

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
        $channelRepository = $container->get('sylius.repository.channel');
        assert(method_exists($channelRepository, 'findOneBy'));
        /** @var ChannelInterface|null $channel */
        $channel = $channelRepository->findOneBy([]);

        $this->assertNotNull($channel, 'Un canal doit exister dans l\'application de test');

        $channel->setThemeName(null);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $entityManager->flush();

        $client->request('GET', '/en_US/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('[data-theme="industry-trade"]');
    }

    /**
     * Test que les composants du thème sont rendus avec leurs classes.
     *
     * Scénario alternatif : un gabarit utilise les composants boutons, tableaux
     * et champs du thème, et ils sont rendus avec leurs classes spécifiques.
     */
    public function testThemeComponentsAreRenderedWithClasses(): void
    {
        $client = self::createClient();
        $container = self::getContainer();

        // Activer le thème
        $channelRepository = $container->get('sylius.repository.channel');
        assert(method_exists($channelRepository, 'findOneBy'));
        /** @var ChannelInterface|null $channel */
        $channel = $channelRepository->findOneBy([]);

        $this->assertNotNull($channel, 'Un canal doit exister dans l\'application de test');

        $channel->setThemeName('industrie-negoce');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $entityManager->flush();

        // Faire une requête vers la route de test qui rend les composants
        $client->request('GET', '/en_US/_test/components');

        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        assert(is_string($content));

        // Vérifier que les classes du thème sont présentes
        $this->assertStringContainsString('industry-btn-primary', $content);
        $this->assertStringContainsString('industry-table-header', $content);
        $this->assertStringContainsString('industry-form-control', $content);
    }
}
