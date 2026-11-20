<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Stub;

use Symfony\Component\HttpFoundation\RequestStack;
use Tmonier\SyliusIndustryTradeTheme\Shop\RegistrationLinkPolicyInterface;

/**
 * Simule la règle d'une application dont les inscriptions se ferment (démonstration publique, boutique sur invitation) :
 * le lien disparaît quand `?registrations_closed` est présent. Pilotée par la requête, elle vérifie au passage que le
 * thème consulte la décision à chaque rendu et non une fois pour toutes.
 */
final class ClosableRegistrationLinkPolicy implements RegistrationLinkPolicyInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {}

    public function isVisible(): bool
    {
        return ! $this->requestStack->getCurrentRequest()?->query
            ->has('registrations_closed');
    }
}
