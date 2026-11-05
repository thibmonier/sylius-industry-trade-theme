<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Session\SessionFactoryInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;

/**
 * US-224 : panier, compte et pages de contenu du thème, rendus dans une application Sylius.
 */
final class CartAccountPagesTest extends WebTestCase
{
    private const THEME = 'tmonier/sylius-industry-trade-theme';

    public function testCartWithTwelveLinesShowsReferenceQuantityAndPricesExcludingTax(): void
    {
        $client = $this->clientWithCart(12);

        $client->request('GET', '/en_US/cart/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorCount(12, 'table.industry-cart-table tbody tr');
        $this->assertSelectorCount(12, 'table.industry-cart-table tbody td.industry-cart-reference code');
        $this->assertSelectorCount(12, 'table.industry-cart-table tbody input[type="number"]');
        $this->assertSelectorCount(12, 'table.industry-cart-table tbody [data-test-cart-product-subtotal]');
        $this->assertSelectorTextContains('table.industry-cart-table thead', 'Reference');
        $this->assertSelectorTextContains('table.industry-cart-table thead', 'Unit price excl. tax');
        $this->assertSelectorTextContains('table.industry-cart-table thead', 'Total excl. tax');
    }

    public function testCartQuantitiesAreEditedInTheLiveFormWithoutLeavingThePage(): void
    {
        $client = $this->clientWithCart(2);

        $crawler = $client->request('GET', '/en_US/cart/');

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            1,
            $crawler->filter('[data-controller~="live"] table.industry-cart-table')
                ->count(),
            'Le tableau est dans le composant live du panier',
        );
        $this->assertSame(
            '1',
            $crawler->filter('table.industry-cart-table tbody input[type="number"]')
                ->first()
                ->attr('value'),
        );
    }

    public function testCartControlsHaveAccessibleNamesAndTheSummaryTitleIsNotNested(): void
    {
        $client = $this->clientWithCart(3);

        $client->request('GET', '/en_US/cart/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorCount(3, 'tbody button[data-test-remove-cart-item][aria-label^="Remove "]');
        $this->assertSelectorCount(3, 'tbody input[type="number"][aria-label^="Quantity of "]');
        $this->assertSelectorExists('input[data-test-cart-promotion-coupon-input][aria-label="Coupon code"]');
        $this->assertSelectorExists('.industry-cart-summary h2');
        $this->assertSelectorNotExists('.industry-cart-summary h3');
    }

    public function testCartReferenceIsTheVariantCode(): void
    {
        $client = $this->clientWithCart(1);
        $variant = $this->variants(1)[0];

        $client->request('GET', '/en_US/cart/');

        $this->assertSelectorTextSame('td.industry-cart-reference code', (string) $variant->getCode());
    }

    public function testEmptyCartWithoutQuickOrderPluginLinksToTheCatalogue(): void
    {
        $client = $this->clientWithTheme();

        $client->request('GET', '/en_US/cart/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.industry-cart-empty');
        $this->assertSelectorTextContains('.industry-cart-empty', 'Your cart is empty');
        $this->assertSelectorExists('.industry-cart-empty a[href="/en_US/"]');
        $this->assertSelectorNotExists('[data-test-industry-cart-empty-quick-order]');
    }

    public function testEmptyCartWithQuickOrderPluginPointsToTheQuickOrderInsteadOfTheCatalogue(): void
    {
        $client = $this->clientWithTheme();
        $client->disableReboot();
        $this->registerQuickOrderStub();

        $client->request('GET', '/en_US/cart/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('[data-test-industry-cart-empty-quick-order]');
        $this->assertSelectorNotExists('.industry-cart-empty a[href="/en_US/"]');
    }

    public function testAccountNavigationUsesTheThemeStyle(): void
    {
        $client = $this->clientWithTheme();
        $this->login($client);

        $client->request('GET', '/en_US/account/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('nav .industry-account-nav__list');
        $this->assertSelectorExists('.industry-account-nav__link[aria-current="page"]');
        $this->assertSelectorNotExists('.industry-account-nav__link[href*="company"]');
    }

    public function testAccountNavigationShowsAndStylesTheEntryOfAnAccountPlugin(): void
    {
        $client = $this->clientWithTheme();
        $this->login($client);

        $client->request('GET', '/en_US/account/dashboard?with_company_entry=1');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.industry-account-nav__list .industry-account-nav__link', 'Dashboard');
        $this->assertSelectorTextContains('.industry-account-nav__list', 'Company accounts');
    }

    public function testStandardSyliusAccountPagesStillRenderWithoutAnyAccountPlugin(): void
    {
        $client = $this->clientWithTheme();
        $this->login($client);

        foreach ([
            '/en_US/account/dashboard',
            '/en_US/account/orders/',
            '/en_US/account/address-book/',
            '/en_US/account/change-password',
        ] as $url) {
            $client->request('GET', $url);
            $this->assertResponseIsSuccessful($url);
        }
    }

    public function testContentPageUsesTheThemeHeadingsListsAndTables(): void
    {
        $client = $this->clientWithTheme();

        $client->request('GET', '/_test/content-page');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('article.industry-content h1', 'Conditions de livraison');
        $this->assertSelectorCount(1, 'article.industry-content h2');
        $this->assertSelectorCount(2, 'article.industry-content ul li');
        $this->assertSelectorExists('article.industry-content table thead.industry-table-header');
        $this->assertSelectorExists('article.industry-content table tr.industry-table-row');
    }

    public function testCartWithLinesRendersTheStandardPageOnAChannelWithoutTheTheme(): void
    {
        $client = $this->clientWithCart(3, null);

        $client->request('GET', '/en_US/cart/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorCount(3, 'table[data-test-cart-items] tbody tr');
        $this->assertSelectorNotExists('.industry-cart-table');
    }

    public function testCartHookableSettingsOfTheApplicationAreKeptOnAChannelWithoutTheTheme(): void
    {
        $client = $this->clientWithCart(2, null);

        $client->request('GET', '/en_US/cart/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('[data-host-app="coupon"]');
        $this->assertSelectorExists('[data-host-app="summary"]');
        $this->assertSelectorNotExists('.industry-cart-summary');
        $this->assertSelectorNotExists('input[aria-label="Coupon code"]');
    }

    public function testCartHookableSettingsOfTheApplicationAreKeptOnAThemedChannel(): void
    {
        $client = $this->clientWithCart(2);

        $client->request('GET', '/en_US/cart/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('[data-host-app="coupon"]');
        $this->assertSelectorExists('[data-host-app="summary"]');
        $this->assertSelectorExists('.industry-cart-summary h2');
        $this->assertSelectorExists('input[data-test-cart-promotion-coupon-input][aria-label="Coupon code"]');
    }

    public function testEmptyCartRendersTheStandardPageOnAChannelWithoutTheTheme(): void
    {
        $client = $this->clientWithTheme(null);

        $client->request('GET', '/en_US/cart/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Your cart is empty');
        $this->assertSelectorNotExists('.industry-cart-empty');
    }

    public function testAccountRendersTheStandardMenuOnAChannelWithoutTheTheme(): void
    {
        $client = $this->clientWithTheme(null);
        $this->login($client);

        $client->request('GET', '/en_US/account/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Personal information');
        $this->assertSelectorNotExists('.industry-account-nav');
    }

    public function testContentPageStillRendersOnAChannelWithoutTheTheme(): void
    {
        $client = $this->clientWithTheme(null);

        $client->request('GET', '/_test/content-page-plain');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('article.industry-content h1', 'Conditions de livraison');
    }

    private function clientWithTheme(?string $theme = self::THEME): KernelBrowser
    {
        $client = self::createClient();
        $channel = $this->channel();
        $channel->setThemeName($theme);
        $this->entityManager()
            ->flush();

        return $client;
    }

    private function clientWithCart(int $lines, ?string $theme = self::THEME): KernelBrowser
    {
        $client = $this->clientWithTheme($theme);
        $channel = $this->channel();
        $currency = $channel->getBaseCurrency();
        $this->assertNotNull($currency);

        $order = $this->service('sylius.factory.order', FactoryInterface::class)->createNew();
        $this->assertInstanceOf(OrderInterface::class, $order);
        $order->setChannel($channel);
        $order->setCurrencyCode($currency->getCode());
        $order->setLocaleCode('en_US');
        $order->setTokenValue('industry-cart-' . bin2hex(random_bytes(4)));

        foreach ($this->variants($lines) as $variant) {
            $order->addItem($this->item($variant, $channel));
        }

        $this->entityManager()
            ->persist($order);
        $this->entityManager()
            ->flush();

        $session = $this->service('session.factory', SessionFactoryInterface::class)->createSession();
        $session->set('_sylius.cart.' . $channel->getCode(), $order->getId());
        $session->save();
        $client->getCookieJar()
            ->set(new Cookie($session->getName(), $session->getId()));

        return $client;
    }

    private function item(ProductVariantInterface $variant, ChannelInterface $channel): OrderItemInterface
    {
        $item = $this->service('sylius.factory.order_item', FactoryInterface::class)->createNew();
        $this->assertInstanceOf(OrderItemInterface::class, $item);
        $pricing = $variant->getChannelPricingForChannel($channel);
        $this->assertNotNull($pricing);
        $item->setVariant($variant);
        $item->setUnitPrice((int) $pricing->getPrice());
        $this->service('sylius.modifier.order_item_quantity', OrderItemQuantityModifierInterface::class)->modify(
            $item,
            1,
        );

        return $item;
    }

    /**
     * @return list<ProductVariantInterface>
     */
    private function variants(int $count): array
    {
        $found = $this->service('sylius.repository.product_variant', RepositoryInterface::class)->findBy([], [
            'id' => 'ASC',
        ], $count);
        $variants = [];
        foreach ($found as $variant) {
            $this->assertInstanceOf(ProductVariantInterface::class, $variant);
            $variants[] = $variant;
        }
        $this->assertCount($count, $variants, 'Les fixtures doivent fournir assez de variantes');

        return $variants;
    }

    private function login(KernelBrowser $client): void
    {
        $user = $this->service('sylius.repository.shop_user', RepositoryInterface::class)->findOneBy([]);
        $this->assertInstanceOf(ShopUserInterface::class, $user, 'Un client doit exister');
        $client->loginUser($user, 'shop');
    }

    private function channel(): ChannelInterface
    {
        $channel = $this->service('sylius.repository.channel', RepositoryInterface::class)->findOneBy([]);
        $this->assertInstanceOf(ChannelInterface::class, $channel);

        return $channel;
    }

    private function entityManager(): EntityManagerInterface
    {
        return $this->service('doctrine.orm.entity_manager', EntityManagerInterface::class);
    }

    /**
     * Fait exister le gabarit du plugin quick-order, comme s'il était installé.
     */
    private function registerQuickOrderStub(): void
    {
        $twig = $this->service('twig', Environment::class);
        $loader = new ArrayLoader([
            '@TmonierSyliusQuickOrderPlugin/shop/cart/quick_order_link.html.twig' => '<a href="/quick-order">Quick order</a>',
        ]);
        $twig->setLoader(new ChainLoader([$loader, $twig->getLoader()]));
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function service(string $id, string $class): object
    {
        $service = self::getContainer()->get($id);
        $this->assertInstanceOf($class, $service);

        return $service;
    }
}
