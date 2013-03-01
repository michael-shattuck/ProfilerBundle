<?php

namespace Clamidity\ProfilerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;

class ClamidityProfilerExtension extends Extension
{
    /**
     * @var array
     */
    protected $resources = array(
        'services' => 'services.yml',
    );
    
    /**
     * Loads the services based on your application configuration.
     *
     * @param array $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->process($configuration->getConfigTree(), $configs);

        if ($config['enabled']) {
            $this->loadDefaults($container);
            
            foreach ($config as $key => $value) {
                $container->setParameter($this->getAlias().'.'.$key, $value);
            }
        }
    }

    public function getAlias()
    {
        return 'clamidity_profiler';
    }

    /**
     * Get File Loader
     *
     * @param ContainerBuilder $container
     */
    public function getFileLoader($container)
    {
        return new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
    }

    protected function loadDefaults($container)
    {
        $loader = $this->getFileLoader($container);

        foreach ($this->resources as $resource) {
            $loader->load($resource);
        }
    }
}
