<?php

namespace Oro\Bundle\CookieConsentBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Loads custom .yml configs, adds custom configuration
 */
class OroCookieConsentExtension extends Extension
{
    public const ALIAS = 'oro_cookie_consent';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        // add system_configuration.yml field
        $container->prependExtensionConfig($this->getAlias(), array_intersect_key($config, array_flip(['settings'])));
    }

    public function getAlias()
    {
        return self::ALIAS;
    }

    /**
     * Returns full key name by it's last part
     *
     * @param $name string last part of the key name (one of the class cons can be used)
     * @return string full config path key
     */
    public static function getConfigKeyByName($name)
    {
        return self::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . $name;
    }
}
