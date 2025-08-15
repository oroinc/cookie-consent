<?php

namespace Oro\Bundle\CookieConsentBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ROOT_NODE = 'oro_cookie_consent';

    public const DEFAULT_BANNER_TITLE = 'This website uses cookies to provide you with the best user experience';
    public const DEFAULT_BANNER_TEXT = <<<_TEXT
Cookies are collected to remember your login details,
provide secure login, collect statistics to optimize website performance and deliver content relevant to you.
By continuing to browse the website, you consent to our use of cookies.
_TEXT;
    public const DEFAULT_PAGE_ID = null;

    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                'show_banner' => ['type' => 'boolean', 'value' => false],
                'localized_banner_title' => ['type' => 'array', 'value' => [null => self::DEFAULT_BANNER_TITLE]],
                'localized_banner_text' => ['type' => 'array', 'value' => [null => self::DEFAULT_BANNER_TEXT]],
                'localized_landing_page_id' => ['type' => 'array', 'value' => [null => self::DEFAULT_PAGE_ID]],
            ]
        );

        return $treeBuilder;
    }
}
