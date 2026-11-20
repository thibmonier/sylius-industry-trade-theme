<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Twig;

use Tmonier\SyliusIndustryTradeTheme\Shop\RegistrationLinkPolicyInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Expose la décision aux gabarits du thème : `industry_theme_registration_link_visible()`.
 */
final class RegistrationLinkExtension extends AbstractExtension
{
    public function __construct(
        private readonly RegistrationLinkPolicyInterface $policy,
    ) {}

    public function getFunctions(): array
    {
        return [new TwigFunction('industry_theme_registration_link_visible', $this->policy->isVisible(...))];
    }
}
