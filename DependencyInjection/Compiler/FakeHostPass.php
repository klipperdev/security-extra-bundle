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
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class FakeHostPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('klipper_security_extra.subscriber.fake_host')
                || !$container->hasDefinition('security.http_utils')) {
            return;
        }

        $httpUtilDef = $container->getDefinition('security.http_utils');
        $fakeHost = true;

        if ($container->hasParameter('session.storage.options')) {
            $sessionOpts = $container->getParameter('session.storage.options');
            $fakeHost = !isset($sessionOpts['cookie_domain']) || empty($sessionOpts['cookie_domain']);
        }

        if ($fakeHost && isset($httpUtilDef->getArguments()[2])) {
            $httpUtilDef->replaceArgument(2, null);
        }
    }
}
