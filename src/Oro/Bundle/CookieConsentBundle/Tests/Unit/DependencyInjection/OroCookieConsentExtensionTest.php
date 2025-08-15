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
                        'show_banner' => [
                            'value' => false,
                            'scope' => 'app'
                        ],
                        'localized_banner_title' => [
                            'value' => [null => Configuration::DEFAULT_BANNER_TITLE],
                            'scope' => 'app'
                        ],
                        'localized_banner_text' => [
                            'value' => [null => Configuration::DEFAULT_BANNER_TEXT],
                            'scope' => 'app'
                        ],
                        'localized_landing_page_id' => [
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
