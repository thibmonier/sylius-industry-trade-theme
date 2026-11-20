<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Shop;

/**
 * Décide si le lien « Inscription » de l'en-tête doit s'afficher.
 *
 * Le thème surcharge le gabarit du cœur : sur un canal themé, sa version l'emporte sur celle de
 * l'application, qui ne peut donc plus y poser sa propre condition. Ce contrat lui rend cette décision
 * sans que le thème ait à connaître la règle : une application dont les inscriptions sont fermées
 * (démonstration publique, boutique sur invitation, canal B2B réservé) alias l'interface sur sa propre
 * implémentation. Par défaut le lien s'affiche, comme dans Sylius.
 */
interface RegistrationLinkPolicyInterface
{
    public function isVisible(): bool;
}
