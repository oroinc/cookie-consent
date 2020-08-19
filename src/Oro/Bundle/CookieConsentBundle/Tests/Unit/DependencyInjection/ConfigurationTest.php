<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CookieConsentBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();
        $builder = $configuration->getConfigTreeBuilder();
        $this->assertInstanceOf(TreeBuilder::class, $builder);
    }

    public function testProcessConfiguration()
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $expected = [
            'settings' => [
                'resolved' => true,
                Configuration::PARAM_NAME_SHOW_BANNER => [
                    'value' => false,
                    'scope' => 'app',
                ],
                Configuration::PARAM_NAME_LOCALIZED_BANNER_TEXT => [
                    'value' => [null => Configuration::DEFAULT_BANNER_TEXT],
                    'scope' => 'app',
                ],
                Configuration::PARAM_NAME_LOCALIZED_LANDING_PAGE_ID => [
                    'value' => [null => Configuration::DEFAULT_PAGE_ID],
                    'scope' => 'app'
                ]
            ],
        ];
        $this->assertEquals($expected, $processor->processConfiguration($configuration, []));
    }
}
