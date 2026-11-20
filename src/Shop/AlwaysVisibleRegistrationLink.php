<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Shop;

/**
 * Comportement de Sylius : le lien est toujours proposé. Implémentation retenue tant qu'une application n'alias pas l'interface.
 */
final readonly class AlwaysVisibleRegistrationLink implements RegistrationLinkPolicyInterface
{
    public function isVisible(): bool
    {
        return true;
    }
}
