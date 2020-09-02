<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Functional\Layout\DataProvider;

use Oro\Bundle\CookieConsentBundle\DependencyInjection\Configuration;
use Oro\Bundle\CookieConsentBundle\Migrations\Data\Demo\ORM\EnableCookieBanner;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CookiesBannerProviderTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([EnableCookieBanner::class]);
    }

    public function testBreadcrumbs()
    {
        $crawler = $this->client->request('GET', '/');
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertStringContainsString(
            'cookie-banner-view',
            $crawler->filter('.wrapper')->html()
        );

        $this->assertStringContainsString(
            \json_encode(htmlentities(Configuration::DEFAULT_BANNER_TEXT, ENT_NOQUOTES)),
            $crawler->filter('.wrapper')->html()
        );
    }
}
