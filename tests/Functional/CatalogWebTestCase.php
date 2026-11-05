<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTaxonInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Product\Factory\ProductFactoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Base des tests fonctionnels des pages du catalogue : active le thème sur le canal
 * et crée un catalogue minimal indépendant des fixtures de l'application de test.
 */
abstract class CatalogWebTestCase extends WebTestCase
{
    protected const LOCALE = 'en_US';

    protected KernelBrowser $client;

    protected EntityManagerInterface $em;

    protected ChannelInterface $channel;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $container = self::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine.orm.entity_manager');
        $this->em = $em;

        $channelRepository = $container->get('sylius.repository.channel');
        assert(method_exists($channelRepository, 'findOneBy'));
        $channel = $channelRepository->findOneBy([]);
        assert($channel instanceof ChannelInterface);
        $channel->setThemeName('tmonier/sylius-industry-trade-theme');
        $this->channel = $channel;
        $this->em->flush();
    }

    protected function createTaxon(string $name): TaxonInterface
    {
        $factory = self::getContainer()->get('sylius.factory.taxon');
        assert($factory instanceof FactoryInterface);
        $code = 'cat_' . uniqid();

        /** @var TaxonInterface $taxon */
        $taxon = $factory->createNew();
        $taxon->setCode($code);
        $taxon->setCurrentLocale(self::LOCALE);
        $taxon->setFallbackLocale(self::LOCALE);
        $taxon->setName($name);
        $taxon->setSlug($code);
        $this->em->persist($taxon);

        return $taxon;
    }

    /**
     * @param array{tracked?: bool, onHand?: int} $stock
     */
    protected function createProduct(
        string $code,
        string $name,
        TaxonInterface $taxon,
        int $price = 12345,
        array $stock = [],
    ): ProductInterface {
        $code .= '-' . substr(uniqid(), -6);
        $container = self::getContainer();
        $productFactory = $container->get('sylius.factory.product');
        $pricingFactory = $container->get('sylius.factory.channel_pricing');
        $productTaxonFactory = $container->get('sylius.factory.product_taxon');
        assert($productFactory instanceof ProductFactoryInterface);
        assert($pricingFactory instanceof FactoryInterface);
        assert($productTaxonFactory instanceof FactoryInterface);

        /** @var ProductInterface $product */
        $product = $productFactory->createWithVariant();
        $product->setCode($code);
        $product->setCurrentLocale(self::LOCALE);
        $product->setFallbackLocale(self::LOCALE);
        $product->setName($name);
        $product->setSlug(strtolower($code));
        $product->addChannel($this->channel);
        $product->setMainTaxon($taxon);

        /** @var ProductVariantInterface $variant */
        $variant = $product->getVariants()
            ->first();
        $variant->setCode($code . '-V');
        $variant->setCurrentLocale(self::LOCALE);
        $variant->setTracked($stock['tracked'] ?? false);
        $variant->setOnHand($stock['onHand'] ?? 0);

        /** @var ChannelPricingInterface $pricing */
        $pricing = $pricingFactory->createNew();
        $pricing->setChannelCode($this->channel->getCode());
        $pricing->setPrice($price);
        $variant->addChannelPricing($pricing);

        /** @var ProductTaxonInterface $productTaxon */
        $productTaxon = $productTaxonFactory->createNew();
        $productTaxon->setProduct($product);
        $productTaxon->setTaxon($taxon);
        $product->addProductTaxon($productTaxon);

        $this->em->persist($product);

        return $product;
    }

    protected function listUrl(TaxonInterface $taxon): string
    {
        return '/' . self::LOCALE . '/taxons/' . $taxon->getSlug();
    }

    protected function showUrl(ProductInterface $product): string
    {
        return '/' . self::LOCALE . '/products/' . $product->getSlug();
    }
}
