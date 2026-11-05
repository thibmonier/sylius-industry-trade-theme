<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Stub;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Simule la règle d'une application qui masque les prix (ex. visiteurs non connectés) : refus quand `?hide_prices` est présent.
 *
 * @extends Voter<string, mixed>
 */
final class HidePricesVoter extends Voter
{
    public const ATTRIBUTE = 'HOST_APP_SEE_PRICES';

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::ATTRIBUTE;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        return ! $this->requestStack->getCurrentRequest()?->query
            ->has('hide_prices');
    }
}
