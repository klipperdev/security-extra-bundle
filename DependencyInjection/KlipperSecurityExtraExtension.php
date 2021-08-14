<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Bundle\SecurityExtraBundle\DependencyInjection;

use Geocoder\Geocoder;
use JMS\SerializerBundle\JMSSerializerBundle;
use Klipper\Bundle\SecurityExtraBundle\Listener\SecurityFakeHostSubscriber;
use Klipper\Component\HttpFoundation\Util\RequestUtil;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class KlipperSecurityExtraExtension extends Extension
{
    /**
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('authentication.xml');
        $loader->load('batch.xml');
        $loader->load('orm_listener.xml');
        $loader->load('orm_filter_listener.xml');

        $loader->load('security_permission_listener.xml');
        $loader->load('resource_domain_listener.xml');
        $loader->load('validator.xml');
        $loader->load('form.xml');
        $loader->load('form_firewall.xml');
        $loader->load('command.xml');
        $loader->load('messenger.xml');

        if (class_exists(JMSSerializerBundle::class)) {
            $loader->load('serializer.xml');
        }

        $this->configUserOrganization($container, $config['user_organization']);
        $this->configRoleFilter($container, $config['role_filter']);
        $this->configOrganizationalContext($container, $config['organizational_filter']);
        $this->configSharingEntryManager($container, $config['sharing_entries']);
        $this->configDoctrineSqlFilter($container, $config['doctrine']['orm']['permissions']['disable_filters']);
        $this->configureFakeHost($container, $config['fake_host']);
        $this->configValidator($container, $config['validator']);
        $this->configLogonAudit($container, $loader, $config['logon_audit']);
        $this->configAnnotations($container, $config['annotations']);
    }

    /**
     * Configure the organization of user.
     *
     * @param ContainerBuilder $container The container builder
     * @param array            $config    The config
     */
    protected function configUserOrganization(ContainerBuilder $container, array $config): void
    {
        $def = $container->getDefinition('klipper_security_extra.orm.subscriber.user');
        $def->replaceArgument(3, $config['default_roles']);
    }

    /**
     * Configure the doctrine role filter.
     *
     * @param ContainerBuilder $container The container builder
     * @param array            $config    The config
     */
    protected function configRoleFilter(ContainerBuilder $container, array $config): void
    {
        $container->setParameter('klipper_security_extra.doctrine.filter.role.excluded_roles', $config['excluded_roles']);
    }

    /**
     * Configure the organizational context.
     *
     * @param ContainerBuilder $container The container builder
     * @param array            $config    The config
     */
    protected function configOrganizationalContext(ContainerBuilder $container, array $config): void
    {
        $container->setParameter('klipper_security_extra.config.organizational_filter', $config);
    }

    /**
     * Configure the security sharing entry manager.
     *
     * @param ContainerBuilder $container The container builder
     * @param array            $config    The config of mailer
     */
    protected function configSharingEntryManager(ContainerBuilder $container, array $config): void
    {
        $container->setParameter('klipper_security_extra.config.sharing_entry_manager', $config);
    }

    /**
     * Configure the permission doctrine sql filter subscriber.
     *
     * @param ContainerBuilder $container The container builder
     * @param array            $config    The config of subscriber
     */
    protected function configDoctrineSqlFilter(ContainerBuilder $container, array $config): void
    {
        $container->getDefinition('klipper_security_extra.subscriber.permission_doctrine_filter')
            ->replaceArgument(1, $config)
        ;
    }

    /**
     * Configure the fake host.
     *
     * @param ContainerBuilder $container The container builder
     * @param bool|string      $enable    Check if the fake host is enabled
     */
    protected function configureFakeHost(ContainerBuilder $container, $enable): void
    {
        if (class_exists(RequestUtil::class)
            && $container->getParameterBag()->resolveValue($enable)) {
            $def = new Definition(SecurityFakeHostSubscriber::class, [
                new Reference('security.firewall.map'),
            ]);
            $def->setPublic(false);
            $def->addTag('kernel.event_subscriber');
            $container->setDefinition('klipper_security_extra.subscriber.fake_host', $def);
        }
    }

    /**
     * Configure the validator.
     *
     * @param ContainerBuilder $container The container builder
     * @param array            $config    The config
     */
    protected function configValidator(ContainerBuilder $container, array $config): void
    {
        $reservedNameDef = $container->getDefinition('klipper_security_extra.validator.reserved_name');
        $reservedNameDef->replaceArgument(0, $config['reserved_names']);
    }

    /**
     * Configure the logon audit.
     *
     * @param ContainerBuilder $container The container builder
     * @param LoaderInterface  $loader    The config loader
     * @param array            $config    The config
     *
     * @throws
     */
    protected function configLogonAudit(ContainerBuilder $container, LoaderInterface $loader, array $config): void
    {
        if ($config['enabled'] && interface_exists(Geocoder::class)) {
            $loader->load('logon_audit.xml');

            $def = $container->getDefinition('klipper_security_extra.subscriber.logon_audit');
            $def->replaceArgument(2, $config['class']);

            if ($config['geocoder']['enabled']) {
                $geocodeRef = new Reference('bazinga_geocoder.geocoder');
                $def->addMethodCall('setGeocoder', [$geocodeRef, $config['geocoder']['provider']]);
            }
        }
    }

    /**
     * Configure the annotations.
     *
     * @param ContainerBuilder $container The container builder
     * @param array            $config    The config
     *
     * @throws
     */
    protected function configAnnotations(ContainerBuilder $container, array $config): void
    {
        $container->setParameter('klipper_security_extra.config.annotations', $config);
    }
}
