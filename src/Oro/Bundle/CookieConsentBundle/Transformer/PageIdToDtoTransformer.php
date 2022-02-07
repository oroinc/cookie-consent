<?php

namespace Oro\Bundle\CookieConsentBundle\Transformer;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CookieConsentBundle\Transformer\DTO\Page as PageDTO;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;

/**
 * This logic transforms cms page id to cms page dto in case when original page found
 */
class PageIdToDtoTransformer
{
    private DoctrineHelper $doctrineHelper;
    private LocalizationHelper $localizationHelper;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        LocalizationHelper $localizationHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->localizationHelper = $localizationHelper;
    }

    public function transform(int $pageId) : ?PageDTO
    {
        $pageRepository = $this->doctrineHelper->getEntityRepositoryForClass(Page::class);
        /** @var Page $cmsPage */
        $cmsPage = $pageRepository->find($pageId);
        if (null === $cmsPage) {
            return null;
        }

        /** @var Localization $localization */
        $localization = $this->localizationHelper->getCurrentLocalization();

        $slug = $cmsPage->getSlugByLocalization($localization);
        if (null === $slug) {
            $slug = $cmsPage->getBaseSlug();
        }

        $pageUrl = (string) $slug;
        $title = (string) $cmsPage->getTitle($localization);

        return PageDTO::create($title, $pageUrl);
    }
}
