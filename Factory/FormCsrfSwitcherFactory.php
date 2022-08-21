<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Bundle\SecurityExtraBundle\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FirewallListenerFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Factory for organizational context.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class FormCsrfSwitcherFactory implements AuthenticatorFactoryInterface, FirewallListenerFactoryInterface
{
    public function getPriority(): int
    {
        return -40;
    }

    public function createAuthenticator(ContainerBuilder $container, string $firewallName, array $config, string $userProviderId): array
    {
        return [];
    }

    public function getKey(): string
    {
        return 'form_csrf';
    }

    public function addConfiguration(NodeDefinition $builder): void
    {
        /* @var ArrayNodeDefinition $builder */
        $builder
            ->canBeDisabled()
        ;
    }

    public function createListeners(ContainerBuilder $container, string $firewallName, array $config): array
    {
        $listenerId = 'klipper_security_extra.authenticator.form_csrf_switcher.firewall_listener.'.$firewallName;
        $container
            ->setDefinition($listenerId, new ChildDefinition('klipper_security_extra.authenticator.form_csrf_switcher.firewall_listener'))
            ->replaceArgument(1, $config)
        ;

        return [$listenerId];
    }
}
