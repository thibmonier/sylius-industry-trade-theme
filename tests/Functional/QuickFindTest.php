<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Functional;

/**
 * Saisie rapide au clavier de la liste dense (US-227) : le comportement du script est vérifié par
 * `QuickFindScriptTest` ; ici, le balisage rendu et l'absence de changement sans le thème.
 */
final class QuickFindTest extends CatalogWebTestCase
{
    public function testReferenceFieldIsAnAccessibleComboboxLinkedToItsListboxAndLiveRegion(): void
    {
        $taxon = $this->createTaxon('Gloves');
        $this->createProduct('GLV001', 'Handling gloves', $taxon);
        $this->em->flush();

        $this->client->request('GET', $this->listUrl($taxon));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('label[for="industry-quick-find-input"]');
        $this->assertSelectorExists(
            'input#industry-quick-find-input[role="combobox"][aria-autocomplete="list"][aria-controls="industry-quick-find-list"][aria-expanded="false"]',
        );
        $this->assertSelectorExists('ul#industry-quick-find-list[role="listbox"]');
        $this->assertSelectorExists('[data-industry-quick-find-status][role="status"][aria-live="polite"]');
        $this->assertSelectorExists('script[data-industry-quick-find-script]');
    }

    public function testEachOptionCarriesItsReferenceAndPointsAtTheLinkOfItsRow(): void
    {
        $taxon = $this->createTaxon('Valves');
        $this->createProduct('VAL001', 'Ball valve', $taxon);
        $this->createProduct('VAL002', 'Gate valve', $taxon);
        $this->em->flush();

        $this->client->request('GET', $this->listUrl($taxon));

        $crawler = $this->client->getCrawler();
        $options = $crawler->filter('[data-industry-quick-find-list] [role="option"]');
        $this->assertCount(2, $options);
        $this->assertStringContainsString('val001', (string) $options->first()->attr('data-search'));
        $this->assertStringContainsString('ball valve', (string) $options->first()->attr('data-search'));
        foreach ($options->each(static fn($option): ?string => $option->attr('data-target')) as $target) {
            $this->assertCount(1, $crawler->filter('a#' . $target), 'l\'option doit cibler le lien d\'une ligne');
        }
    }

    public function testNothingIsAddedOnAChannelWithoutTheTheme(): void
    {
        $taxon = $this->createTaxon('Seals');
        $this->createProduct('SEAL01', 'O-ring', $taxon);
        $this->channel->setThemeName(null);
        $this->em->flush();

        $this->client->request('GET', $this->listUrl($taxon));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('[data-industry-quick-find]');
        $this->assertSelectorNotExists('script[data-industry-quick-find-script]');
    }
}
