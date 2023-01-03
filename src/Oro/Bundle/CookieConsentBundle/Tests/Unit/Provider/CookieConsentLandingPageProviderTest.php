<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CookieConsentBundle\Helper\LocalizedValueExtractor;
use Oro\Bundle\CookieConsentBundle\Provider\CookieConsentLandingPageProvider;
use Oro\Bundle\CookieConsentBundle\Transformer\DTO\Page;
use Oro\Bundle\CookieConsentBundle\Transformer\PageIdToDtoTransformer;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Component\Testing\Unit\EntityTrait;

class CookieConsentLandingPageProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var PageIdToDtoTransformer|\PHPUnit\Framework\MockObject\MockObject */
    private $pageIdToDtoTransformer;

    /** @var CookieConsentLandingPageProvider */
    private $landingPageProvider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->pageIdToDtoTransformer = $this->createMock(PageIdToDtoTransformer::class);

        $this->landingPageProvider = new CookieConsentLandingPageProvider(
            $this->configManager,
            new LocalizedValueExtractor(),
            $this->pageIdToDtoTransformer
        );
    }

    public function testGetPageDtoByLocalization()
    {
        $pageId = 5;
        $page = Page::create('page_title', '/url');
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_cookie_consent.localized_landing_page_id')
            ->willReturn([null => $pageId]);

        $localizationId = 1;
        $localization = $this->getEntity(Localization::class, ['id' => $localizationId]);

        $this->pageIdToDtoTransformer->expects(self::once())
            ->method('transform')
            ->with($pageId)
            ->willReturn($page);

        self::assertEquals($page, $this->landingPageProvider->getPageDtoByLocalization($localization));
    }

    public function testGetPageDtoByLocalizationWillReturnEmptyPage()
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_cookie_consent.localized_landing_page_id')
            ->willReturn([]);

        $localizationId = 1;
        $localization = $this->getEntity(Localization::class, ['id' => $localizationId]);

        $this->pageIdToDtoTransformer->expects(self::never())
            ->method('transform');

        self::assertNull($this->landingPageProvider->getPageDtoByLocalization($localization));
    }
}
