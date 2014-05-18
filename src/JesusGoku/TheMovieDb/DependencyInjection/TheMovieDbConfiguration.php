<?php

namespace JesusGoku\TheMovieDb\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class TheMovieDbConfiguration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('the_movie_db');

        $rootNode
            ->children()
                ->scalarNode('api_base_url')
                    ->isRequired()
                ->end()
                ->scalarNode('api_key')
                    ->isRequired()
                ->end()
                ->scalarNode('language')
                    ->defaultValue('es')
                ->end()
                ->scalarNode('backdrop_size')
                    ->defaultValue('w1280')
                ->end()
                ->scalarNode('poster_size')
                    ->defaultValue('w500')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

}
