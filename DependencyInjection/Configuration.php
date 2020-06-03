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

use Klipper\Bundle\SecurityBundle\DependencyInjection\NodeUtils;
use Klipper\Component\SecurityExtra\Model\LogonAuditInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your config files.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('klipper_security_extra');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('fake_host')->defaultValue('%kernel.debug%')->end()
            ->arrayNode('role_filter')
            ->addDefaultsIfNotSet()
            ->children()
            ->arrayNode('excluded_roles')
            ->prototype('scalar')->end()
            ->end()
            ->end()
            ->end()
            ->append($this->getAnnotationNode())
            ->arrayNode('organizational_filter')
            ->addDefaultsIfNotSet()
            ->children()
            ->arrayNode('excluded_classes')
            ->prototype('scalar')->end()
            ->end()
            ->arrayNode('user_excluded_orgs_classes')
            ->prototype('scalar')->end()
            ->end()
            ->arrayNode('optional_all_filter_classes')
            ->prototype('scalar')->end()
            ->end()
            ->end()
            ->end()
            ->arrayNode('sharing_entries')
            ->fixXmlConfig('sharing_entry')
            ->useAttributeAsKey('class', false)
            ->normalizeKeys(false)
            ->prototype('array')
            ->children()
            ->scalarNode('field')->isRequired()->end()
            ->scalarNode('repository_method')->defaultNull()->end()
            ->end()
            ->end()
            ->end()
            ->arrayNode('doctrine')
            ->addDefaultsIfNotSet()
            ->children()
            ->arrayNode('orm')
            ->addDefaultsIfNotSet()
            ->children()
            ->arrayNode('permissions')
            ->addDefaultsIfNotSet()
            ->children()
            ->arrayNode('disable_filters')
            ->prototype('scalar')->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->arrayNode('validator')
            ->addDefaultsIfNotSet()
            ->children()
            ->arrayNode('reserved_names')
            ->prototype('scalar')->end()
            ->end()
            ->end()
            ->end()
            ->arrayNode('logon_audit')
            ->addDefaultsIfNotSet()
            ->canBeEnabled()
            ->children()
            ->scalarNode('class')->defaultValue(LogonAuditInterface::class)->end()
            ->arrayNode('geocoder')
            ->addDefaultsIfNotSet()
            ->canBeEnabled()
            ->children()
            ->scalarNode('provider')->defaultValue('free_geo_ip')->end()
            ->end()
            ->end()
            ->end()
            ->end()

            ->end()
        ;

        return $treeBuilder;
    }

    /**
     * Get annotation node.
     */
    private function getAnnotationNode(): NodeDefinition
    {
        return NodeUtils::createArrayNode('annotations')
            ->addDefaultsIfNotSet()
            ->children()
            ->arrayNode('include_paths')
            ->defaultValue(['%kernel.project_dir%/src'])
            ->scalarPrototype()->end()
            ->end()
            ->booleanNode('sharing_entry')->defaultTrue()->end()
            ->booleanNode('organizational_filters')->defaultTrue()->end()
            ->end()
            ;
    }
}
