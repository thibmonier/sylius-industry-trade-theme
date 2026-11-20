<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Point d'extension du lien « Inscription » (0.3.2) dans une vraie application.
 *
 * L'application de test alias RegistrationLinkPolicyInterface sur ClosableRegistrationLinkPolicy, qui ferme le lien
 * quand `?registrations_closed` est présent. Les deux requêtes vérifient donc que le thème consulte la décision à
 * chaque rendu, et non une fois pour toutes au démarrage du conteneur.
 */
final class RegistrationLinkTest extends WebTestCase
{
    private const MARKER = 'register-page-button';

    public function testTheThemeShowsTheLinkWhenTheApplicationAllowsIt(): void
    {
        $client = $this->clientOnThemedChannel();

        $client->request('GET', '/en_US/');

        $this->assertResponseIsSuccessful();
        self::assertStringContainsString(self::MARKER, $this->contentOf($client));
    }

    public function testTheThemeHidesTheLinkWhenTheApplicationClosesRegistrations(): void
    {
        $client = $this->clientOnThemedChannel();

        $client->request('GET', '/en_US/?registrations_closed=1');

        $this->assertResponseIsSuccessful();
        self::assertStringNotContainsString(
            self::MARKER,
            $this->contentOf($client),
            'Une boutique aux inscriptions fermées ne doit pas proposer un lien menant à un 403.',
        );
    }

    private function clientOnThemedChannel(): KernelBrowser
    {
        $client = self::createClient();
        $container = self::getContainer();

        $channelRepository = $container->get('sylius.repository.channel');
        assert(method_exists($channelRepository, 'findOneBy'));
        /** @var ChannelInterface|null $channel */
        $channel = $channelRepository->findOneBy([]);
        self::assertNotNull($channel, 'Un canal doit exister dans l\'application de test');

        $channel->setThemeName('tmonier/sylius-industry-trade-theme');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $entityManager->flush();

        return $client;
    }

    private function contentOf(KernelBrowser $client): string
    {
        $content = $client->getResponse()
            ->getContent();
        self::assertIsString($content);

        return $content;
    }
}
