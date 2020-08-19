<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CookieConsentBundle\DependencyInjection\OroCookieConsentExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroCookieConsentExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroCookieConsentExtension());

        $this->assertExtensionConfigsLoaded([OroCookieConsentExtension::ALIAS]);
    }

    public function testGetAlias()
    {
        $extension = new OroCookieConsentExtension();
        $this->assertEquals(OroCookieConsentExtension::ALIAS, $extension->getAlias());
    }
}
