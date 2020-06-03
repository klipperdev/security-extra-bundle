<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Bundle\SecurityExtraBundle;

use Klipper\Bundle\SecurityExtraBundle\DependencyInjection\Compiler\FakeHostPass;
use Klipper\Bundle\SecurityExtraBundle\DependencyInjection\Compiler\KlipperSecurityExtraExtensionDelayPass;
use Klipper\Bundle\SecurityExtraBundle\DependencyInjection\Compiler\OrganizationalFilterExcludedClassesLoaderPass;
use Klipper\Bundle\SecurityExtraBundle\DependencyInjection\Compiler\OrganizationalFilterOptionalAllFilterClassesLoaderPass;
use Klipper\Bundle\SecurityExtraBundle\DependencyInjection\Compiler\OrganizationalFilterUserExcludedOrgsClassesLoaderPass;
use Klipper\Bundle\SecurityExtraBundle\DependencyInjection\Compiler\SharingEntryLabelBuilderPass;
use Klipper\Bundle\SecurityExtraBundle\DependencyInjection\Compiler\SharingEntryLoaderPass;
use Klipper\Component\SecurityExtra\Factory\FormCsrfSwitcherFactory;
use Klipper\Component\SecurityExtra\Factory\FormPermissionCheckerFactory;
use Klipper\Component\SecurityExtra\Factory\OrganizationalContextFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class KlipperSecurityExtraBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new KlipperSecurityExtraExtensionDelayPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 10);
        $container->addCompilerPass(new SharingEntryLoaderPass());
        $container->addCompilerPass(new OrganizationalFilterExcludedClassesLoaderPass());
        $container->addCompilerPass(new OrganizationalFilterUserExcludedOrgsClassesLoaderPass());
        $container->addCompilerPass(new OrganizationalFilterOptionalAllFilterClassesLoaderPass());
        $container->addCompilerPass(new SharingEntryLabelBuilderPass());
        $container->addCompilerPass(new FakeHostPass(), PassConfig::TYPE_BEFORE_REMOVING, -10);

        /** @var SecurityExtension $extension */
        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new OrganizationalContextFactory());
        $extension->addSecurityListenerFactory(new FormCsrfSwitcherFactory());
        $extension->addSecurityListenerFactory(new FormPermissionCheckerFactory());
    }
}
