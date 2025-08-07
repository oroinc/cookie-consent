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

        $configManager = self::getConfigManager();
        $configManager->set('oro_cookie_consent.show_banner', true);
        $configManager->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_cookie_consent.show_banner', false);
        $configManager->flush();
    }

    public function testBreadcrumbs(): void
    {
        $crawler = $this->client->request('GET', '/');
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);

        self::assertStringContainsString(
            'cookie-banner-view',
            $crawler->filter('.wrapper')->html()
        );

        $cookieBannerData = $crawler
            ->filter('div[class=" cookie-banner-view"]')
            ->attr('data-page-component-view');
        $cookieBannerData = json_decode($cookieBannerData, flags: JSON_THROW_ON_ERROR);
        self::assertStringContainsString(Configuration::DEFAULT_BANNER_TITLE, $cookieBannerData->bannerTitle);
        self::assertStringContainsString(Configuration::DEFAULT_BANNER_TEXT, $cookieBannerData->bannerText);
    }
}
