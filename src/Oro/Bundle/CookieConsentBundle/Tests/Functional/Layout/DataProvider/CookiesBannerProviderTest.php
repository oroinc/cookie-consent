<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Functional\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CookieConsentBundle\DependencyInjection\Configuration;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CookiesBannerProviderTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->enableCookieBanner(true);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->enableCookieBanner(false);
        parent::tearDown();
    }

    private function enableCookieBanner(bool $enabled): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_cookie_consent.show_banner', $enabled);
        $configManager->flush();
    }

    public function testBreadcrumbs()
    {
        $crawler = $this->client->request('GET', '/');
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertStringContainsString(
            'cookie-banner-view',
            $crawler->filter('.wrapper')->html()
        );

        $cookieBannerData = $crawler
            ->filter('div[class=" cookie-banner-view"]')
            ->attr('data-page-component-view');
        $cookieBannerData = json_decode($cookieBannerData);
        $this->assertStringContainsString($cookieBannerData->bannerTitle, Configuration::DEFAULT_BANNER_TITLE);
        $this->assertStringContainsString($cookieBannerData->bannerText, Configuration::DEFAULT_BANNER_TEXT);
    }
}
