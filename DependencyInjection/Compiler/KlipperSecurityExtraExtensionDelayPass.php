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

use Doctrine\Common\Annotations\Reader;
use JMS\SerializerBundle\JMSSerializerBundle;
use Klipper\Bundle\MetadataBundle\KlipperMetadataBundle;
use Klipper\Bundle\SecurityExtraBundle\DependencyInjection\KlipperSecurityExtraExtension;
use Klipper\Component\SecurityExtra\Sharing\Loader\AnnotationLoader;
use Klipper\Component\SecurityExtra\Sharing\SharingEntryConfig;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class KlipperSecurityExtraExtensionDelayPass implements CompilerPassInterface
{
    /**
     * @throws
     */
    public function process(ContainerBuilder $container): void
    {
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../../Resources/config'));

        $this->configOrganizationalContext($container, $loader);
        $this->configSharing($container, $loader);
        $this->configSharingEntryManager($container);
        $this->configAnnotations($container, $loader);

        // Clean parameters
        $pb = $container->getParameterBag();
        $pb->remove('klipper_security_extra.config.organizational_filter');
        $pb->remove('klipper_security_extra.config.annotations');
        $pb->remove('klipper_security_extra.config.sharing_entry_manager');
    }

    /**
     * Configure the organizational context.
     *
     * @param ContainerBuilder $container The container builder
     * @param LoaderInterface  $loader    The config loader
     *
     * @throws
     */
    protected function configOrganizationalContext(ContainerBuilder $container, LoaderInterface $loader): void
    {
        if (!$container->has('klipper_security.organizational_context')) {
            return;
        }

        $loader->load('organizational_context.xml');
        $loader->load('organizational_context_listener.xml');
        $loader->load('organizational_orm_validation.xml');
        $loader->load('organizational_orm_listener.xml');
        $loader->load('organizational_orm_filter_listener.xml');

        $config = $container->getParameter('klipper_security_extra.config.organizational_filter');

        $container->getDefinition('klipper_security_extra.organizational_filter.excluded_classes_loader.configuration')
            ->replaceArgument(0, $config['excluded_classes'])
        ;

        $container->getDefinition('klipper_security_extra.organizational_filter.user_excluded_orgs_classes_loader.configuration')
            ->replaceArgument(0, $config['user_excluded_orgs_classes'])
        ;

        $container->getDefinition('klipper_security_extra.organizational_filter.optional_all_filter_classes_loader.configuration')
            ->replaceArgument(0, $config['optional_all_filter_classes'])
        ;
    }

    /**
     * Configure the security sharing.
     *
     * @param ContainerBuilder $container The container builder
     * @param LoaderInterface  $loader    The config loader
     *
     * @throws
     */
    protected function configSharing(ContainerBuilder $container, LoaderInterface $loader): void
    {
        if (!$container->hasDefinition('klipper_security.sharing_manager')) {
            return;
        }

        $loader->load('security_sharing.xml');
        $loader->load('security_sharing_batch.xml');

        if (class_exists(JMSSerializerBundle::class)) {
            $loader->load('serializer.xml');

            if (class_exists(KlipperMetadataBundle::class)
                    && $container->hasDefinition('klipper_metadata.permission_metadata_manager')) {
                $loader->load('serializer_metadata.xml');
            }
        }
    }

    /**
     * Configure the security sharing entry manager.
     *
     * @param ContainerBuilder $container The container builder
     *
     * @throws
     */
    protected function configSharingEntryManager(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('klipper_security.sharing_manager')) {
            return;
        }

        $config = $container->getParameter('klipper_security_extra.config.sharing_entry_manager');
        $def = $container->getDefinition('klipper_security_extra.sharing_entry_loader.configuration');
        $configs = $def->getArgument(0);

        foreach ($config as $class => $entryConfig) {
            $cDef = new Definition(SharingEntryConfig::class, [
                $class,
                $entryConfig['field'],
                $entryConfig['repository_method'],
            ]);
            $cDef->setPublic(false);

            $id = uniqid('klipper_security_extra.sharing_entry_config_', true);
            $container->setDefinition($id, $cDef);
            $configs[] = new Reference($id);
        }

        $def->replaceArgument(0, $configs);
    }

    /**
     * Configure the annotations.
     *
     * @param ContainerBuilder $container The container builder
     * @param LoaderInterface  $loader    The config loader
     *
     * @throws
     */
    protected function configAnnotations(ContainerBuilder $container, LoaderInterface $loader): void
    {
        /** @var KlipperSecurityExtraExtension $ext */
        $ext = $container->getExtension('klipper_security_extra');
        $config = $container->getParameter('klipper_security_extra.config.annotations');

        if (interface_exists(Reader::class) && class_exists(Finder::class)) {
            if ($config['organizational_filters']
                    && $container->has('klipper_security.organizational_context')) {
                $loader->load('organizational_annotation_filter.xml');
                $this->addIncludePaths(
                    $container->getDefinition('klipper_security_extra.organizational_filter.array_resource'),
                    $config['include_paths']
                );

                $ext->addAnnotatedClassesToCompile([
                    AnnotationLoader::class,
                ]);
            }

            if ($config['sharing_entry']
                    && $container->hasDefinition('klipper_security.sharing_manager')) {
                $loader->load('annotation_sharing.xml');
                $this->addIncludePaths(
                    $container->getDefinition('klipper_security_extra.sharing_entry.array_resource'),
                    $config['include_paths']
                );

                $ext->addAnnotatedClassesToCompile([
                    AnnotationLoader::class,
                ]);
            }
        }
    }

    /**
     * @param Definition $def   The service definition
     * @param string[]   $paths The paths
     */
    private function addIncludePaths(Definition $def, array $paths): void
    {
        foreach ($paths as $path) {
            $def->addMethodCall('add', [$path, 'annotation']);
        }
    }
}
