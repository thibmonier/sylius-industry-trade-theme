<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Functional;

use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpFoundation\Session\SessionFactoryInterface;

/**
 * US-228 : le thème s'active dans une application qui a ses propres hookables sans les perdre.
 *
 * L'application de test déclare des hookables comme le ferait une application hôte
 * (tests/TestApplication/config/packages/a_host_app.yaml).
 */
final class HostAppCompatibilityTest extends CatalogWebTestCase
{
    public function testHookableAddedByTheApplicationToTheHeaderIsRenderedOnAThemedChannel(): void
    {
        $this->client->request('GET', '/' . self::LOCALE . '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('[data-theme="industry-trade"]');
        $this->assertSelectorTextSame('header [data-host-app="header"]', 'host app');
    }

    public function testHeaderKeepsTheHookablesOfTheCoreOnAThemedChannel(): void
    {
        $this->client->request('GET', '/' . self::LOCALE . '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('header a[href="/' . self::LOCALE . '/"]');
        $this->assertSelectorExists('header [data-live-name-value="sylius_shop:cart:widget"]');
    }

    public function testHookableAddedByTheApplicationToTheFooterIsRenderedOnAThemedChannel(): void
    {
        $this->client->request('GET', '/' . self::LOCALE . '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('footer [data-host-app="footer"]', 'host app');
    }

    public function testHookableAddedByTheApplicationToTheProductListIsRenderedOnAThemedChannel(): void
    {
        $taxon = $this->createTaxon('Fasteners');
        $this->createProduct('HOST01', 'Hex bolt', $taxon);
        $this->em->flush();

        $this->client->request('GET', $this->listUrl($taxon));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('table.industry-product-table');
        $this->assertSelectorTextSame('[data-host-app="product-list"]', 'host app');
    }

    public function testLayoutKeepsTheRouteAttributeOfTheCoreAndMarksTheThemeOnTheBody(): void
    {
        $this->client->request('GET', '/' . self::LOCALE . '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body[data-theme="industry-trade"][data-route="sylius_shop_homepage"]');
    }

    public function testPriceHiddenByTheApplicationStaysHiddenInTheDenseList(): void
    {
        $taxon = $this->createTaxon('Fasteners');
        $this->createProduct('HIDE01', 'Hex bolt', $taxon, 1250);
        $this->em->flush();

        $this->client->request('GET', $this->listUrl($taxon) . '?hide_prices=1');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('tr[data-industry="product-row"]');
        $this->assertSelectorNotExists('[data-industry="price"]');
        $this->assertStringNotContainsString('12.50', (string) $this->client->getResponse()->getContent());
        $this->assertSelectorNotExists('.industry-excl-vat');
    }

    public function testPriceIsShownInTheDenseListWhenTheApplicationAllowsIt(): void
    {
        $taxon = $this->createTaxon('Fasteners');
        $this->createProduct('SHOW01', 'Hex bolt', $taxon, 1250);
        $this->em->flush();

        $this->client->request('GET', $this->listUrl($taxon));

        $this->assertSelectorTextContains('[data-industry="price"]', '12.50');
        $this->assertSelectorExists('.industry-excl-vat');
    }

    public function testActionHookablesOfTheProductCardAreRenderedOncePerRowWithoutDuplicatingTheCoreCard(): void
    {
        $taxon = $this->createTaxon('Fasteners');
        $this->createProduct('ACT01', 'Hex bolt', $taxon);
        $this->createProduct('ACT02', 'Hex nut', $taxon);
        $this->em->flush();

        $this->client->request('GET', $this->listUrl($taxon));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorCount(2, 'tr[data-industry="product-row"] td [data-host-app="add-to-cart"]');
        $this->assertSelectorCount(2, 'tr[data-industry="product-row"] [data-test-product-name]');
        $this->assertSelectorCount(2, 'tr[data-industry="product-row"] [data-test-product-price]');
    }

    public function testListWrapperScrollsOnItsOwnWhenTheThemeStylesAreNotLoaded(): void
    {
        $taxon = $this->createTaxon('Fasteners');
        $this->createProduct('WRAP01', 'Hex bolt', $taxon);
        $this->em->flush();

        $this->client->request('GET', $this->listUrl($taxon));

        $this->assertSelectorExists('.industry-product-list.overflow-x-auto');
    }

    public function testFlashMessagesAreTranslatedWithTheFlashesDomainOnAThemedChannel(): void
    {
        $factory = self::getContainer()->get('session.factory');
        assert($factory instanceof SessionFactoryInterface);
        $session = $factory->createSession();
        assert($session instanceof FlashBagAwareSessionInterface);
        $session->getFlashBag()
            ->add('success', 'sylius.cart.add_item');
        $session->save();
        $this->client->getCookieJar()
            ->set(new Cookie($session->getName(), $session->getId()));

        $this->client->request('GET', '/' . self::LOCALE . '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert-success', 'Item has been added to cart');
    }
}
