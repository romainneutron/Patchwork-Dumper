<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\DebugBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * DebugExtension.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
class DebugExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $container->setParameter(
            'var_dumper.cloner.class',
            'Symfony\Component\VarDumper\Cloner\\'.(function_exists('symfony_zval_info') ? 'Ext' : 'Php').'Cloner'
        );

        $container->getDefinition('var_dumper.cloner')
            ->addMethodCall('setMaxItems',  array($config['max_items']))
            ->addMethodCall('setMaxString', array($config['max_string_length']));

        if ($config['dump_path']) {
            $container->getDefinition('debug.debug_listener')
                ->replaceArgument(1, 'php://output' === $config['dump_path'] ? 'var_dumper.html_dumper' : 'var_dumper.cli_dumper');

            $container->getDefinition('var_dumper.json_dumper')->addArgument($config['dump_path']);
            $container->getDefinition('var_dumper.cli_dumper')->addArgument($config['dump_path']);
            $container->getDefinition('var_dumper.html_dumper')->addArgument($config['dump_path']);
        }
    }
}
