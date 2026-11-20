<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Unit\Shop;

use PHPUnit\Framework\TestCase;
use Tmonier\SyliusIndustryTradeTheme\Shop\AlwaysVisibleRegistrationLink;
use Tmonier\SyliusIndustryTradeTheme\Shop\RegistrationLinkPolicyInterface;
use Tmonier\SyliusIndustryTradeTheme\Twig\RegistrationLinkExtension;

/**
 * Point d'extension du lien « Inscription » (0.3.2) : le défaut doit rester le comportement de Sylius, et une
 * application qui ferme les inscriptions doit pouvoir le masquer sans copier le gabarit dans un thème enfant.
 */
final class RegistrationLinkPolicyTest extends TestCase
{
    public function testTheDefaultKeepsTheSyliusBehaviour(): void
    {
        self::assertTrue((new AlwaysVisibleRegistrationLink())->isVisible());
    }

    public function testTheTwigFunctionReportsWhatThePolicyDecides(): void
    {
        $visible = $this->functionOf(new AlwaysVisibleRegistrationLink());

        self::assertTrue($visible());
    }

    public function testAnApplicationCanCloseTheLink(): void
    {
        $closed = new class implements RegistrationLinkPolicyInterface {
            public function isVisible(): bool
            {
                return false;
            }
        };

        self::assertFalse($this->functionOf($closed)());
    }

    public function testTheDecisionIsTakenAtCallTimeNotAtBuildTime(): void
    {
        // Une application dont la règle dépend de la requête (mode démonstration, canal, rôle) doit être consultée
        // à chaque rendu : la fonction Twig ne doit pas figer la valeur au moment où l'extension est construite.
        $switching = new class implements RegistrationLinkPolicyInterface {
            public bool $open = true;

            public function isVisible(): bool
            {
                return $this->open;
            }
        };

        $visible = $this->functionOf($switching);
        self::assertTrue($visible());

        $switching->open = false;
        self::assertFalse($visible());
    }

    private function functionOf(RegistrationLinkPolicyInterface $policy): \Closure
    {
        $functions = (new RegistrationLinkExtension($policy))->getFunctions();
        self::assertCount(1, $functions);
        self::assertSame('industry_theme_registration_link_visible', $functions[0]->getName());

        $callable = $functions[0]->getCallable();
        if (! \is_callable($callable)) {
            self::fail("La fonction Twig n'expose pas d'appelable.");
        }

        return $callable(...);
    }
}
