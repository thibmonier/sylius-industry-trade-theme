<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Functional;

use Sylius\Component\Attribute\Factory\AttributeFactoryInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

/**
 * Pages du catalogue du thème (US-223) : accueil, liste dense, fiche produit.
 */
final class CatalogPagesTest extends CatalogWebTestCase
{
    public function testListShowsReferenceDesignationStockAndPriceOnOneRow(): void
    {
        $taxon = $this->createTaxon('Fasteners');
        for ($i = 1; $i <= 200; ++$i) {
            $this->createProduct(sprintf('REF%03d', $i), sprintf('Hex bolt %d', $i), $taxon, 1250, [
                'tracked' => true,
                'onHand' => $i,
            ]);
        }
        $this->em->flush();

        $this->client->request('GET', $this->listUrl($taxon));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('table.industry-product-table');
        $rows = $this->client->getCrawler()
            ->filter('tr[data-industry="product-row"]');
        $this->assertGreaterThan(0, $rows->count());
        $first = $rows->first();
        $this->assertCount(1, $first->filter('[data-industry="reference"]'));
        $this->assertMatchesRegularExpression(
            '/^REF\d{3}-[0-9a-f]{6}-V$/',
            trim($first->filter('[data-industry="reference"]')->text()),
        );
        $this->assertStringContainsString('Hex bolt', $first->filter('th a')->text());
        $this->assertStringContainsString('In stock', $first->filter('[data-industry="stock"]')->text());
        $this->assertStringContainsString('12.50', $first->filter('[data-industry="price"]')->text());
        $this->assertSelectorTextContains('.industry-product-table thead', 'Price excl. VAT');
    }

    public function testEveryCellOfTheListCarriesItsLabelForTheCardLayout(): void
    {
        $taxon = $this->createTaxon('Valves');
        $this->createProduct('VAL001', 'Ball valve', $taxon);
        $this->em->flush();

        $this->client->request('GET', $this->listUrl($taxon));

        $this->assertResponseIsSuccessful();
        $cells = $this->client->getCrawler()
            ->filter('tr[data-industry="product-row"] > td, tr[data-industry="product-row"] > th');
        $this->assertCount(5, $cells);
        foreach ($cells->each(static fn($cell): ?string => $cell->attr('data-label')) as $label) {
            $this->assertNotSame('', trim((string) $label));
        }
    }

    public function testProductWithoutImageShowsAPlaceholder(): void
    {
        $taxon = $this->createTaxon('Seals');
        $this->createProduct('SEAL01', 'O-ring', $taxon);
        $this->em->flush();

        $this->client->request('GET', $this->listUrl($taxon));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('[data-industry="image-placeholder"][role="img"][aria-label="No image available"]');
        $this->assertSelectorNotExists('.industry-product-table img');
    }

    public function testProductPageShowsReferencesTechnicalSheetDocumentsAndAvailability(): void
    {
        $taxon = $this->createTaxon('Pumps');
        $product = $this->createProduct('PUMP01', 'Centrifugal pump', $taxon, 99900, [
            'tracked' => true,
            'onHand' => 4,
        ]);
        $this->addTextAttribute($product, 'flow_rate', 'Flow rate', '120 m3/h');
        $this->addTextAttribute($product, 'datasheet', 'Datasheet', 'https://example.test/files/pump01.pdf');
        $this->em->flush();

        $this->client->request('GET', $this->showUrl($product));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('[data-industry="product-code"]', (string) $product->getCode());
        $this->assertSelectorTextContains('[data-industry="variant-code"]', $product->getCode() . '-V');
        $this->assertSelectorTextContains('[data-industry="technical-sheet"]', 'Flow rate');
        $this->assertSelectorTextContains('[data-industry="technical-sheet"]', '120 m3/h');
        $this->assertSelectorExists('[data-industry="documents"] a[href="https://example.test/files/pump01.pdf"]');
        $this->assertSelectorNotExists('[data-industry="technical-sheet"] a[href$=".pdf"]');
        $this->assertSelectorTextContains('[data-industry="availability"]', 'In stock (4)');
    }

    public function testProductPageRendersWithoutTierPricingPlugin(): void
    {
        $taxon = $this->createTaxon('Hoses');
        $product = $this->createProduct('HOSE01', 'Hose', $taxon);
        $this->em->flush();

        $this->client->request('GET', $this->showUrl($product));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('[data-industry="negotiated-prices"]');
        $this->assertSelectorNotExists('[data-test-tmonier-tier-prices]');
    }

    public function testHomepageShowsHeroAndAislesOfTheMenuTaxon(): void
    {
        $root = $this->createTaxon('Catalogue');
        $child = $this->createTaxon('Bearings');
        $child->setParent($root);
        $this->channel->setMenuTaxon($root);
        $this->em->flush();

        $this->client->request('GET', '/' . self::LOCALE . '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('section[data-industry="home-hero"] h1');
        $this->assertSelectorTextContains('[data-industry="home-aisles"]', 'Bearings');
        $this->assertSelectorExists('[data-industry="home-aisles"] a[href*="/taxons/"]');
    }

    public function testHomepageWithoutMenuTaxonHasNoAislesAndNoError(): void
    {
        $this->channel->setMenuTaxon(null);
        $this->em->flush();

        $this->client->request('GET', '/' . self::LOCALE . '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('[data-industry="home-hero"]');
        $this->assertSelectorNotExists('[data-industry="home-aisles"]');
    }

    private function addTextAttribute(ProductInterface $product, string $code, string $name, string $value): void
    {
        $container = self::getContainer();
        $attributeFactory = $container->get('sylius.factory.product_attribute');
        $valueFactory = $container->get('sylius.factory.product_attribute_value');
        assert($attributeFactory instanceof AttributeFactoryInterface);
        assert($valueFactory instanceof FactoryInterface);

        $attribute = $attributeFactory->createTyped('text');
        $attribute->setCode($code . '_' . uniqid());
        $attribute->setCurrentLocale(self::LOCALE);
        $attribute->setFallbackLocale(self::LOCALE);
        $attribute->setName($name);
        $this->em->persist($attribute);

        /** @var ProductAttributeValueInterface $attributeValue */
        $attributeValue = $valueFactory->createNew();
        $attributeValue->setAttribute($attribute);
        $attributeValue->setLocaleCode(self::LOCALE);
        $attributeValue->setValue($value);
        $product->addAttribute($attributeValue);
    }
}
