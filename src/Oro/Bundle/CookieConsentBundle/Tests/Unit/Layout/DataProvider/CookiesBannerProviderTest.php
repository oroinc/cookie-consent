<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CookieConsentBundle\Helper\CookiesAcceptedPropertyHelper;
use Oro\Bundle\CookieConsentBundle\Helper\FrontendRepresentativeUserHelper;
use Oro\Bundle\CookieConsentBundle\Helper\LocalizedValueExtractor;
use Oro\Bundle\CookieConsentBundle\Layout\DataProvider\CookiesBannerProvider;
use Oro\Bundle\CookieConsentBundle\Provider\CookieConsentLandingPageProviderInterface;
use Oro\Bundle\CookieConsentBundle\Tests\Unit\Stubs\CustomerUserStub;
use Oro\Bundle\CookieConsentBundle\Tests\Unit\Stubs\CustomerVisitorStub;
use Oro\Bundle\CookieConsentBundle\Transformer\DTO\Page;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CookiesBannerProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var FrontendRepresentativeUserHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $frontendRepresentativeUserHelper;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var CookieConsentLandingPageProviderInterface|\PHPUnit\Framework\MockObject\MockObject  */
    private $landingPageProvider;

    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationHelper;

    private LocalizedValueExtractor $localizedValueExtractor;
    private CookiesAcceptedPropertyHelper $cookiesAcceptedPropertyHelper;
    private CookiesBannerProvider $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->cookiesAcceptedPropertyHelper = new CookiesAcceptedPropertyHelper();
        $this->frontendRepresentativeUserHelper = $this->createMock(FrontendRepresentativeUserHelper::class);
        $this->localizedValueExtractor = new LocalizedValueExtractor();
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->landingPageProvider = $this->createMock(CookieConsentLandingPageProviderInterface::class);

        $htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $htmlTagHelper->expects(self::any())
            ->method('purify')
            ->willReturnCallback(function ($inputString) {
                return $inputString . '_purified_';
            });

        $this->provider = new CookiesBannerProvider(
            $this->frontendRepresentativeUserHelper,
            $this->cookiesAcceptedPropertyHelper,
            $this->landingPageProvider,
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

    public function testGetPageTitle()
    {
        $page = Page::create('page_title', '/url');
        $localizationId = 1;
        $localization = $this->getEntity(Localization::class, ['id' => $localizationId]);

        $this->localizationHelper
            ->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $this->landingPageProvider
            ->expects($this->once())
            ->method('getPageDtoByLocalization')
            ->with($localization)
            ->willReturn($page);

        self::assertEquals('page_title_purified_', $this->provider->getPageTitle());
    }

    public function testGetPageTitleEmpty()
    {
        $localizationId = 1;
        $localization = $this->getEntity(Localization::class, ['id' => $localizationId]);

        $this->localizationHelper
            ->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $this->landingPageProvider
            ->expects($this->once())
            ->method('getPageDtoByLocalization')
            ->with($localization)
            ->willReturn(null);

        self::assertEquals('', $this->provider->getPageTitle());
    }

    public function testGetPageUrl()
    {
        $page = Page::create('page_title', '/url');
        $localizationId = 1;
        $localization = $this->getEntity(Localization::class, ['id' => $localizationId]);

        $this->localizationHelper
            ->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $this->landingPageProvider
            ->expects($this->once())
            ->method('getPageDtoByLocalization')
            ->with($localization)
            ->willReturn($page);

        self::assertEquals('/url', $this->provider->getPageUrl());
    }

    public function testGetPageUrlEmpty()
    {
        $localizationId = 1;
        $localization = $this->getEntity(Localization::class, ['id' => $localizationId]);

        $this->localizationHelper
            ->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $this->landingPageProvider
            ->expects($this->once())
            ->method('getPageDtoByLocalization')
            ->with($localization)
            ->willReturn(null);

        self::assertEquals('', $this->provider->getPageUrl());
    }
}
