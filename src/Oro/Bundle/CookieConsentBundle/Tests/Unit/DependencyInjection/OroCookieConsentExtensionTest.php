<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CookieConsentBundle\DependencyInjection\Configuration;
use Oro\Bundle\CookieConsentBundle\DependencyInjection\OroCookieConsentExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroCookieConsentExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroCookieConsentExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        Configuration::PARAM_NAME_SHOW_BANNER => [
                            'value' => false,
                            'scope' => 'app'
                        ],
                        Configuration::PARAM_NAME_LOCALIZED_BANNER_TITLE => [
                            'value' => [null => Configuration::DEFAULT_BANNER_TITLE],
                            'scope' => 'app'
                        ],
                        Configuration::PARAM_NAME_LOCALIZED_BANNER_TEXT => [
                            'value' => [null => Configuration::DEFAULT_BANNER_TEXT],
                            'scope' => 'app'
                        ],
                        Configuration::PARAM_NAME_LOCALIZED_LANDING_PAGE_ID => [
                            'value' => [null => null],
                            'scope' => 'app'
                        ]
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_cookie_consent')
        );
    }
}
