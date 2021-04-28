<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('webgriffe_sylius_nexi_plugin');
        $rootNode = $treeBuilder->getRootNode();

        return $treeBuilder;
    }
}
