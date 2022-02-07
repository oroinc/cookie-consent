<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Functional\Transformer;

use Oro\Bundle\CookieConsentBundle\Tests\Functional\DataFixtures\LoadPageFixture;
use Oro\Bundle\CookieConsentBundle\Transformer\PageIdToDtoTransformer;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;

class PageIdToDTOTransformerTest extends FrontendWebTestCase
{
    private PageIdToDtoTransformer $pageIdToDTOTransformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
        $this->setCurrentWebsite('default');
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            LoadPageFixture::class
        ]);

        $this->pageIdToDTOTransformer = new PageIdToDtoTransformer(
            self::getContainer()->get('oro_entity.doctrine_helper'),
            self::getContainer()->get('oro_locale.helper.localization')
        );
    }

    public function testGetPageDTOByIdByNotExistedId()
    {
        $this->assertNull($this->pageIdToDTOTransformer->transform(PHP_INT_MAX));
    }

    public function testGetPageDTOByIdWithDefaultLocalization()
    {
        $existedPage = $this->getReference(LoadPageFixture::PAGE_WITH_DEFAULT_LOCALIZATION);
        $page = $this->pageIdToDTOTransformer->transform($existedPage->getId());
        $this->assertNotNull($page);
        $this->assertEquals(LoadPageFixture::TITLE_WITH_DEFAULT_LOCALIZATION, $page->getTitle());
        $this->assertEquals(LoadPageFixture::URL_WITH_DEFAULT_LOCALIZATION, $page->getUrl());
    }

    public function testGetPageDTOByIdWithBaseLocalization()
    {
        $existedPage = $this->getReference(LoadPageFixture::PAGE_WITH_BASE_LOCALIZATION);
        $page = $this->pageIdToDTOTransformer->transform($existedPage->getId());
        $this->assertNotNull($page);
        $this->assertEquals(LoadPageFixture::TITLE_WITH_BASE_LOCALIZATION, $page->getTitle());
        $this->assertEquals(LoadPageFixture::URL_WITH_BASE_LOCALIZATION, $page->getUrl());
    }
}
