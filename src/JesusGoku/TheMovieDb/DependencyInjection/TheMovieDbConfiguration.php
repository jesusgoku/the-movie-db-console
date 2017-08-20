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
                ->arrayNode('themoviedb')
                    ->children()
                        ->scalarNode('api_base_url')
                            ->isRequired()
                        ->end()
                        ->scalarNode('api_key')
                            ->isRequired()
                        ->end()
                        ->scalarNode('language')
                            ->defaultValue('en')
                        ->end()
                        ->scalarNode('backdrop_size')
                            ->defaultValue('original')
                        ->end()
                        ->scalarNode('poster_size')
                            ->defaultValue('original')
                        ->end()
                        ->scalarNode('backdrop_size_preview')
                            ->defaultValue('w780')
                        ->end()
                        ->scalarNode('poster_size_preview')
                            ->defaultValue('w500')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('thetvdb')
                    ->children()
                        ->scalarNode('api_base_url')
                            ->isRequired()
                        ->end()
                        ->scalarNode('api_key')
                            ->isRequired()
                        ->end()
                        ->scalarNode('language')
                            ->defaultValue('en')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

}
