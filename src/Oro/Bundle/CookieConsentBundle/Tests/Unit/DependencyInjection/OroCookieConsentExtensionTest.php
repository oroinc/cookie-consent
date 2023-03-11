<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CookieConsentBundle\DependencyInjection\OroCookieConsentExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroCookieConsentExtensionTest extends \PHPUnit\Framework\TestCase
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
                        'show_banner' => ['value' => false, 'scope' => 'app'],
                        'localized_banner_text' => [
                            'value' => [
                                null => '<h3 style="text-align:center;">This website uses cookies to provide you'
                                    . " with the best user experience</h3>\n"
                                    . "Cookies are collected to remember your login details,\n"
                                    . 'provide secure login, collect statistics to optimize website performance'
                                    . " and deliver content relevant to you.<br>\n"
                                    . 'By continuing to browse the website, you consent to our use of cookies.'
                            ],
                            'scope' => 'app'
                        ],
                        'localized_landing_page_id' => ['value' => [null => null], 'scope' => 'app']
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_cookie_consent')
        );
    }
}
