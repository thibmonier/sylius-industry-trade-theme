<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Stub;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
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

    /**
     * Le 4e paramètre est obligatoire en Symfony 8 ; en 7.4 le parent ne le déclare qu'en commentaire, l'ajouter ici reste compatible.
     */
    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        return ! $this->requestStack->getCurrentRequest()?->query
            ->has('hide_prices');
    }
}
