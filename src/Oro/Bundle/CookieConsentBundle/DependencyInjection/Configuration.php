<?php

namespace Oro\Bundle\CookieConsentBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ROOT_NODE = 'oro_cookie_consent';
    public const PARAM_NAME_SHOW_BANNER = 'show_banner';
    public const PARAM_NAME_LOCALIZED_BANNER_TITLE = 'localized_banner_title';
    public const PARAM_NAME_LOCALIZED_BANNER_TEXT = 'localized_banner_text';
    public const PARAM_NAME_LOCALIZED_LANDING_PAGE_ID = 'localized_landing_page_id';

    public const DEFAULT_BANNER_TITLE = 'This website uses cookies to provide you with the best user experience';
    public const DEFAULT_BANNER_TEXT = <<<_TEXT
Cookies are collected to remember your login details,
provide secure login, collect statistics to optimize website performance and deliver content relevant to you.
By continuing to browse the website, you consent to our use of cookies.
_TEXT;
    public const DEFAULT_PAGE_ID = null;

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                self::PARAM_NAME_SHOW_BANNER  => [
                    'type'  => 'boolean',
                    'value' => false
                ],
                self::PARAM_NAME_LOCALIZED_BANNER_TITLE => [
                    'type'  => 'array',
                    'value' => [null => self::DEFAULT_BANNER_TITLE]
                ],
                self::PARAM_NAME_LOCALIZED_BANNER_TEXT => [
                    'type'  => 'array',
                    'value' => [null => self::DEFAULT_BANNER_TEXT]
                ],
                self::PARAM_NAME_LOCALIZED_LANDING_PAGE_ID => [
                    'type'  => 'array',
                    'value' => [null => self::DEFAULT_PAGE_ID]
                ],
            ]
        );

        return $treeBuilder;
    }

    public static function getConfigKeyByName(string $name): string
    {
        return self::ROOT_NODE . ConfigManager::SECTION_MODEL_SEPARATOR . $name;
    }
}
