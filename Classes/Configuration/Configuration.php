<?php

namespace Tehplague\CdnAssets\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 * @package Tehplague\CdnAssets\Configuration
 * @author Christian Spoo <cs@marketing-factory.de>
 */
class Configuration implements ConfigurationInterface
{

    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('cdn_assets');
        $rootNode
            ->children()
                ->arrayNode('mappings')
                    ->useAttributeAsKey('key')
                    ->arrayPrototype()
                        ->children()
                            ->booleanNode('enableCDN')
                                ->beforeNormalization()
                                ->ifInArray(['0', '1'])
                                    ->then(function ($value) {
                                        return ('1' === $value);
                                    })
                                ->end()
                            ->end()
                            ->scalarNode('cdnHost')->end()
                            ->booleanNode('cdnUseSSL')
                                ->beforeNormalization()
                                ->ifInArray(['0', '1'])
                                    ->then(function ($value) {
                                        return ('1' === $value);
                                    })
                                ->end()
                                ->defaultFalse()
                            ->end()
                            ->scalarNode('jsonAssetManifest')->end()
                            ->arrayNode('resources')
                                ->children()
                                    ->scalarNode('cssPrefix')
                                        ->treatNullLike('')
                                    ->end()
                                    ->scalarNode('jsPrefix')
                                        ->treatNullLike('')
                                    ->end()
                                    ->arrayNode('css')
                                        ->useAttributeAsKey('key')
                                        ->prototype('scalar')
                                        ->end()
                                    ->end()
                                    ->arrayNode('js')
                                        ->useAttributeAsKey('key')
                                        ->prototype('scalar')
                                        ->end()
                                    ->end()
                                    ->arrayNode('jsFooter')
                                        ->useAttributeAsKey('key')
                                        ->prototype('scalar')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
