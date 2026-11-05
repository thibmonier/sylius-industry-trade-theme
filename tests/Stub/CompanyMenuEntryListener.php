<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Stub;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Simule l'entrée « Entreprise » qu'ajoute le plugin company-accounts au menu du compte.
 * Elle n'apparaît que si la requête porte `with_company_entry=1` : sans elle, le menu est celui du cœur.
 */
final class CompanyMenuEntryListener
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {}

    public function __invoke(MenuBuilderEvent $event): void
    {
        if ($this->requestStack->getCurrentRequest()?->query->get('with_company_entry') !== '1') {
            return;
        }

        $event->getMenu()
            ->addChild('company', [
                'route' => 'sylius_shop_homepage',
            ])
            ->setLabel('Company accounts')
        ;
    }
}
