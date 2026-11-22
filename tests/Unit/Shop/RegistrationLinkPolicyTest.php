<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Unit\Shop;

use PHPUnit\Framework\TestCase;
use Tmonier\SyliusIndustryTradeTheme\Shop\AlwaysVisibleRegistrationLink;
use Tmonier\SyliusIndustryTradeTheme\Shop\RegistrationLinkPolicyInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Point d'extension du lien « Inscription » (0.3.2).
 *
 * Le thème surcharge ce gabarit depuis la 0.3.1 : sur un canal themé, sa version l'emporte sur celle de l'application,
 * qui ne peut donc plus y poser sa condition. Trois garanties sont vérifiées ici : le défaut reste le comportement de
 * Sylius, une application peut masquer le lien, et surtout une installation qui n'a PAS remis à jour sa copie de
 * `config/packages/tmonier_sylius_industry_trade_theme.yaml` rend le lien au lieu d'une erreur.
 */
final class RegistrationLinkPolicyTest extends TestCase
{
    private const TEMPLATE = __DIR__
        . '/../../../templates/bundles/SyliusShopBundle/shared/layout/base/header/content/security/visitor/desktop/register.html.twig';

    public function testTheDefaultKeepsTheSyliusBehaviour(): void
    {
        self::assertTrue((new AlwaysVisibleRegistrationLink())->isVisible());
    }

    public function testAnInstallationWithoutTheGlobalStillRendersTheLink(): void
    {
        // Le cas d'une application qui monte en 0.3.2 sans recopier le fichier de configuration du thème : le global
        // est absent. Sans `|default(null)` dans le gabarit, Twig lèverait ici une erreur sur toutes les pages de la
        // boutique, et une amélioration d'accessibilité deviendrait une panne générale.
        $html = $this->render([]);

        self::assertStringContainsString('register-page-button', $html);
    }

    public function testAnApplicationCanHideTheLink(): void
    {
        $html = $this->render([
            'industry_theme_registration_link_policy' => $this->policy(false),
        ]);

        self::assertStringNotContainsString('register-page-button', $html);
    }

    public function testAnApplicationCanKeepTheLink(): void
    {
        $html = $this->render([
            'industry_theme_registration_link_policy' => $this->policy(true),
        ]);

        self::assertStringContainsString('register-page-button', $html);
    }

    private function policy(bool $visible): RegistrationLinkPolicyInterface
    {
        return new class ($visible) implements RegistrationLinkPolicyInterface {
            public function __construct(
                private readonly bool $visible,
            ) {}

            public function isVisible(): bool
            {
                return $this->visible;
            }
        };
    }

    /**
     * Rend le vrai gabarit du thème dans un Twig nu, `strict_variables` activé comme dans Sylius : c'est ce réglage
     * qui fait échouer une variable absente, donc la condition à vérifier.
     *
     * @param array<string, mixed> $context
     */
    private function render(array $context): string
    {
        $template = file_get_contents(self::TEMPLATE);
        self::assertIsString($template, 'Le gabarit du lien « Inscription » doit être lisible.');

        $twig = new Environment(
            new ArrayLoader([
                'register.html.twig' => $template,
                // Macro du cœur réduite à ce que le gabarit en utilise.
                '@SyliusShop/shared/buttons.html.twig' => '{% macro transparent(url, label, id, icon, extra) %}'
                    . '<a id="{{ id }}" href="{{ url }}">{{ label }}</a>{% endmacro %}',
            ]),
            [
                'strict_variables' => true,
            ],
        );
        $twig->addFunction(new TwigFunction('path', static fn(string $route): string => '/' . $route));
        $twig->addFilter(new TwigFilter('trans', static fn(string $key): string => $key));

        return $twig->render('register.html.twig', $context);
    }
}
