<?php

namespace Clamidity\ProfilerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * This class contains the configuration information for the bundle
 *
 * This information is solely responsible for how the different configuration
 * sections are normalized, and merged.
 *
 * @author Alex Kleissner <hex337@gmail.com>
 */
class Configuration
{
    /**
     * Generates the configuration tree.
     *
     * @return \Symfony\Component\DependencyInjection\Configuration\NodeInterface
     */
    public function getConfigTree()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('clamidity_profiler');

        $rootNode
            ->children()
                ->scalarNode('location_reports')->defaultNull()->end()
                ->scalarNode('file_extension')->defaultValue('symfony')->end()
                ->scalarNode('overwrite')->defaultFalse()->end()
                ->scalarNode('enabled')->defaultFalse()->end()
            ->end();

        return $treeBuilder->buildTree();
    }
}
