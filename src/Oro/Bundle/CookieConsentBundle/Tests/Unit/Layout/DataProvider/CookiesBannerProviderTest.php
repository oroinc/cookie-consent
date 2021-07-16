<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CookieConsentBundle\Helper\CookiesAcceptedPropertyHelper;
use Oro\Bundle\CookieConsentBundle\Helper\FrontendRepresentativeUserHelper;
use Oro\Bundle\CookieConsentBundle\Helper\LocalizedValueExtractor;
use Oro\Bundle\CookieConsentBundle\Layout\DataProvider\CookiesBannerProvider;
use Oro\Bundle\CookieConsentBundle\Tests\Unit\Stubs\CustomerUserStub;
use Oro\Bundle\CookieConsentBundle\Tests\Unit\Stubs\CustomerVisitorStub;
use Oro\Bundle\CookieConsentBundle\Transformer\DTO\Page;
use Oro\Bundle\CookieConsentBundle\Transformer\PageIdToDTOTransformer;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CookiesBannerProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var FrontendRepresentativeUserHelper | \PHPUnit\Framework\MockObject\MockObject */
    private $frontendRepresentativeUserHelper;

    /** @var CookiesAcceptedPropertyHelper */
    private $cookiesAcceptedPropertyHelper;

    /** @var PageIdToDTOTransformer | \PHPUnit\Framework\MockObject\MockObject */
    private $pageIdToDTOTransformer;

    /** @var LocalizedValueExtractor */
    private $localizedValueExtractor;

    /** @var ConfigManager | \PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var LocalizationHelper */
    private $localizationHelper;

    /** @var CookiesBannerProvider */
    private $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->cookiesAcceptedPropertyHelper = new CookiesAcceptedPropertyHelper();
        $this->frontendRepresentativeUserHelper = $this->createMock(FrontendRepresentativeUserHelper::class);
        $this->pageIdToDTOTransformer = $this->createMock(PageIdToDTOTransformer::class);
        $this->localizedValueExtractor = new LocalizedValueExtractor();
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);

        $htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $htmlTagHelper->expects(self::any())
            ->method('purify')
            ->willReturnCallback(function ($inputString) {
                return $inputString . '_purified_';
            });

        $this->provider = new CookiesBannerProvider(
            $this->frontendRepresentativeUserHelper,
            $this->cookiesAcceptedPropertyHelper,
            $this->pageIdToDTOTransformer,
            $this->localizedValueExtractor,
            $this->configManager,
            $this->localizationHelper,
            $htmlTagHelper
        );
    }

    public function testIsBannerVisibleWhenItDisabledInConfig()
    {
        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with('oro_cookie_consent.show_banner')
            ->willReturn(false);

        $this->frontendRepresentativeUserHelper
            ->expects(self::never())
            ->method('getRepresentativeUser');

        self::assertFalse($this->provider->isBannerVisible());
    }

    /**
     * @dataProvider isBannerVisibleWhenItEnabledInConfigProvider
     *
     * @param object|null $frontendRepresentativeUser
     * @param bool $expectedResult
     */
    public function testIsBannerVisibleWhenItEnabledInConfig($frontendRepresentativeUser, bool $expectedResult)
    {
        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with('oro_cookie_consent.show_banner')
            ->willReturn(true);

        $this->frontendRepresentativeUserHelper
            ->expects(self::once())
            ->method('getRepresentativeUser')
            ->willReturn($frontendRepresentativeUser);

        self::assertEquals($expectedResult, $this->provider->isBannerVisible());
    }

    /**
     * @return array
     */
    public function isBannerVisibleWhenItEnabledInConfigProvider()
    {
        return [
            'Representative User not found' => [
                'frontendRepresentativeUser' => null,
                'expectedResult' => true
            ],
            'Representative User is CustomerVisitor with not accepted cookies' => [
                'frontendRepresentativeUser' => new CustomerVisitorStub(false),
                'expectedResult' => true
            ],
            'Representative User is CustomerVisitor with accepted cookies' => [
                'frontendRepresentativeUser' => new CustomerVisitorStub(true),
                'expectedResult' => false
            ],
            'Representative User is CustomerUser with not accepted cookies' => [
                'frontendRepresentativeUser' => new CustomerUserStub(false),
                'expectedResult' => true
            ],
            'Representative User is CustomerUser with accepted cookies' => [
                'frontendRepresentativeUser' => new CustomerUserStub(true),
                'expectedResult' => false
            ],
        ];
    }

    public function testIsPageExistAndLandingPageNotSet()
    {
        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with('oro_cookie_consent.localized_landing_page_id')
            ->willReturn([null => null]);

        $this->pageIdToDTOTransformer
            ->expects(self::never())
            ->method('transform');

        self::assertFalse($this->provider->isPageExist());
    }

    /**
     * @dataProvider isPageExistAndLandingPageIsSetProvider
     */
    public function testIsPageExistAndLandingPageIsSet(bool $expectedResult, Page $page = null)
    {
        $landingPageId = 111;

        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with('oro_cookie_consent.localized_landing_page_id')
            ->willReturn([null => $landingPageId]);

        $this->pageIdToDTOTransformer
            ->expects(self::once())
            ->method('transform')
            ->with($landingPageId)
            ->willReturn($page);

        self::assertEquals($expectedResult, $this->provider->isPageExist());
    }

    public function testGetBannerText()
    {
        $bannerText = 'Cookie Consent Banner Text';

        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with('oro_cookie_consent.localized_banner_text')
            ->willReturn([null => $bannerText]);

        self::assertEquals($bannerText . '_purified_', $this->provider->getBannerText());
    }

    /**
     * @return array
     */
    public function isPageExistAndLandingPageIsSetProvider()
    {
        return [
            'Page exists' => [
                'expectedResult' => true,
                'page' => Page::create('title', 'url'),
            ],
            'Page not exists' => [
                'expectedResult' => false,
                'page' => null,
            ]
        ];
    }

    public function testIsPageExistAndLandingPageIsSetAndCalledTwice()
    {
        $this->testIsPageExistAndLandingPageIsSet(true, Page::create('title', 'slug'));
        self::assertEquals(true, $this->provider->isPageExist());
    }

    public function testGetPageTitleAndLandingPageNotSet()
    {
        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with('oro_cookie_consent.localized_landing_page_id')
            ->willReturn([null => null]);

        $this->pageIdToDTOTransformer
            ->expects(self::never())
            ->method('transform');

        self::assertEquals('', $this->provider->getPageTitle());
    }

    /**
     * @dataProvider getPageTitleAndLandingPageIsSetProvider
     */
    public function testGetPageTitleAndLandingPageIsSet(string $expectedTitle, Page $page = null)
    {
        $landingPageId = 111;

        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with('oro_cookie_consent.localized_landing_page_id')
            ->willReturn([null => $landingPageId]);

        $this->pageIdToDTOTransformer
            ->expects(self::once())
            ->method('transform')
            ->with($landingPageId)
            ->willReturn($page);

        self::assertEquals($expectedTitle, $this->provider->getPageTitle());
    }

    /**
     * @return array
     */
    public function getPageTitleAndLandingPageIsSetProvider()
    {
        return [
            'Page exists' => [
                'expectedTitle' => 'title_purified_',
                'page' => Page::create('title', 'url'),
            ],
            'Page not exists' => [
                'expectedTitle' => '',
                'page' => null,
            ]
        ];
    }

    public function testGetPageUrlAndLandingPageNotSet()
    {
        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with('oro_cookie_consent.localized_landing_page_id')
            ->willReturn([null => null]);

        $this->pageIdToDTOTransformer
            ->expects(self::never())
            ->method('transform');

        self::assertEquals('', $this->provider->getPageUrl());
    }

    /**
     * @dataProvider getGetPageUrlAndLandingPageIsSetProvider
     */
    public function testGetPageUrlAndLandingPageIsSet(string $expectedUrl, Page $page = null)
    {
        $landingPageId = 111;

        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with('oro_cookie_consent.localized_landing_page_id')
            ->willReturn([null => $landingPageId]);

        $this->pageIdToDTOTransformer
            ->expects(self::once())
            ->method('transform')
            ->with($landingPageId)
            ->willReturn($page);

        self::assertEquals($expectedUrl, $this->provider->getPageUrl());
    }

    /**
     * @return array
     */
    public function getGetPageUrlAndLandingPageIsSetProvider()
    {
        return [
            'Page exists' => [
                'expectedUrl' => 'url',
                'page' => Page::create('title', 'url'),
            ],
            'Page not exists' => [
                'expectedUrl' => '',
                'page' => null,
            ]
        ];
    }
}
