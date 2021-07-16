<?php

namespace Oro\Bundle\CookieConsentBundle\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CookieConsentBundle\DependencyInjection\Configuration;
use Oro\Bundle\CookieConsentBundle\DependencyInjection\OroCookieConsentExtension;
use Oro\Bundle\CookieConsentBundle\Helper\CookiesAcceptedPropertyHelper;
use Oro\Bundle\CookieConsentBundle\Helper\FrontendRepresentativeUserHelper;
use Oro\Bundle\CookieConsentBundle\Helper\LocalizedValueExtractor;
use Oro\Bundle\CookieConsentBundle\Transformer\DTO\Page;
use Oro\Bundle\CookieConsentBundle\Transformer\PageIdToDTOTransformer;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;

/**
 * Provides next information:
 * - cookie banner visibility status
 * - data from attached to banner cms page
 */
class CookiesBannerProvider
{
    /** @var Page|null */
    private $cmsPage = null;

    /** @var bool */
    private $cmsPageLoaded = false;

    /** @var PageIdToDTOTransformer */
    private $pageIdToDTOTransformer;

    /** @var LocalizedValueExtractor */
    private $localizedValueExtractor;

    /** @var ConfigManager */
    private $configManager;

    /** @var LocalizationHelper */
    private $localizationHelper;

    /** @var FrontendRepresentativeUserHelper */
    private $frontendRepresentativeUserHelper;

    /** @var CookiesAcceptedPropertyHelper */
    private $cookiesAcceptedPropertyHelper;

    /** @var HtmlTagHelper */
    private $htmlTagHelper;

    public function __construct(
        FrontendRepresentativeUserHelper $frontendRepresentativeUserHelper,
        CookiesAcceptedPropertyHelper $cookiesAcceptedPropertyHelper,
        PageIdToDTOTransformer $pageIdToDTOTransformer,
        LocalizedValueExtractor $localizedValueExtractor,
        ConfigManager $configManager,
        LocalizationHelper $localizationHelper,
        HtmlTagHelper $htmlTagHelper
    ) {
        $this->frontendRepresentativeUserHelper = $frontendRepresentativeUserHelper;
        $this->cookiesAcceptedPropertyHelper = $cookiesAcceptedPropertyHelper;
        $this->pageIdToDTOTransformer = $pageIdToDTOTransformer;
        $this->localizedValueExtractor = $localizedValueExtractor;
        $this->configManager = $configManager;
        $this->localizationHelper = $localizationHelper;
        $this->htmlTagHelper = $htmlTagHelper;
    }

    public function isBannerVisible() : bool
    {
        $showBanner = $this->configManager->get(
            OroCookieConsentExtension::getConfigKeyByName(
                Configuration::PARAM_NAME_SHOW_BANNER
            )
        );
        if (!$showBanner) {
            return false;
        }

        $representativeUser = $this->frontendRepresentativeUserHelper->getRepresentativeUser();

        return false === $this->cookiesAcceptedPropertyHelper->isCookiesAccepted($representativeUser);
    }

    public function getBannerText() : string
    {
        $bannerTexts = $this->configManager->get(
            OroCookieConsentExtension::getConfigKeyByName(
                Configuration::PARAM_NAME_LOCALIZED_BANNER_TEXT
            )
        );

        $localization = $this->localizationHelper->getCurrentLocalization();

        return $this->htmlTagHelper->purify(
            (string)$this->localizedValueExtractor->getLocalizedFallbackValue($bannerTexts, $localization)
        );
    }

    public function isPageExist() : bool
    {
        if ($this->cmsPageLoaded) {
            return null !== $this->cmsPage;
        }

        $landingPageIds = $this->configManager->get(
            OroCookieConsentExtension::getConfigKeyByName(
                Configuration::PARAM_NAME_LOCALIZED_LANDING_PAGE_ID
            )
        );

        $localization = $this->localizationHelper->getCurrentLocalization();

        $landingPageId = $this->localizedValueExtractor->getLocalizedFallbackValue($landingPageIds, $localization);
        if (!$landingPageId) {
            return false;
        }

        $this->cmsPage = $this->pageIdToDTOTransformer->transform($landingPageId);
        $this->cmsPageLoaded = true;

        return null !== $this->cmsPage;
    }

    public function getPageTitle() : string
    {
        if (!$this->isPageExist()) {
            return '';
        }

        return $this->htmlTagHelper->purify($this->cmsPage->getTitle());
    }

    public function getPageUrl() : string
    {
        if (!$this->isPageExist()) {
            return '';
        }

        return $this->cmsPage->getUrl();
    }
}
