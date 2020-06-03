<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Bundle\SecurityExtraBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds all services with the tags "klipper_security_extra.sharing_entry_label_builder" as arguments
 * of the "klipper_security_extra.sharing_entry_manager" service.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class SharingEntryLabelBuilderPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container): void
    {
        $serviceId = 'klipper_security_extra.sharing_entry_manager';
        $tagName = 'klipper_security_extra.sharing_entry_label_builder';

        if (!$container->hasDefinition($serviceId)) {
            return;
        }

        $labelBuilders = [];

        foreach ($this->findAndSortTaggedServices($tagName, $container) as $service) {
            $labelBuilders[] = $service;
        }

        $container->getDefinition($serviceId)->replaceArgument(2, $labelBuilders);
    }
}
