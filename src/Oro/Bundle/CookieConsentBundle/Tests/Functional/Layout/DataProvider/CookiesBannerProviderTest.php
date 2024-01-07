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

        $cookieBannerData = $crawler
            ->filter('div[class=" cookie-banner-view"]')
            ->attr('data-page-component-view');
        $cookieBannerData = json_decode($cookieBannerData);
        $this->assertStringContainsString($cookieBannerData->bannerText, Configuration::DEFAULT_BANNER_TEXT);
    }
}
