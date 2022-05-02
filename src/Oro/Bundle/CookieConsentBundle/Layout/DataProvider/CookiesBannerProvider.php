<?php

namespace Oro\Bundle\CookieConsentBundle\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CookieConsentBundle\DependencyInjection\Configuration;
use Oro\Bundle\CookieConsentBundle\Helper\CookiesAcceptedPropertyHelper;
use Oro\Bundle\CookieConsentBundle\Helper\FrontendRepresentativeUserHelper;
use Oro\Bundle\CookieConsentBundle\Helper\LocalizedValueExtractor;
use Oro\Bundle\CookieConsentBundle\Provider\CookieConsentLandingPageProviderInterface;
use Oro\Bundle\CookieConsentBundle\Transformer\DTO\Page;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;

/**
 * Provides next information:
 * - cookie banner visibility status
 * - data from attached to banner cms page
 */
class CookiesBannerProvider
{
    private ?Page $cmsPage = null;
    private bool $cmsPageLoaded = false;
    private FrontendRepresentativeUserHelper $frontendRepresentativeUserHelper;
    private CookiesAcceptedPropertyHelper $cookiesAcceptedPropertyHelper;
    private CookieConsentLandingPageProviderInterface $landingPageProvider;
    private LocalizedValueExtractor $localizedValueExtractor;
    private ConfigManager $configManager;
    private LocalizationHelper $localizationHelper;
    private HtmlTagHelper $htmlTagHelper;

    public function __construct(
        FrontendRepresentativeUserHelper $frontendRepresentativeUserHelper,
        CookiesAcceptedPropertyHelper $cookiesAcceptedPropertyHelper,
        CookieConsentLandingPageProviderInterface $landingPageProvider,
        LocalizedValueExtractor $localizedValueExtractor,
        ConfigManager $configManager,
        LocalizationHelper $localizationHelper,
        HtmlTagHelper $htmlTagHelper
    ) {
        $this->frontendRepresentativeUserHelper = $frontendRepresentativeUserHelper;
        $this->cookiesAcceptedPropertyHelper = $cookiesAcceptedPropertyHelper;
        $this->landingPageProvider = $landingPageProvider;
        $this->localizedValueExtractor = $localizedValueExtractor;
        $this->configManager = $configManager;
        $this->localizationHelper = $localizationHelper;
        $this->htmlTagHelper = $htmlTagHelper;
    }

    public function isBannerVisible(): bool
    {
        $showBanner = $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::PARAM_NAME_SHOW_BANNER)
        );
        if (!$showBanner) {
            return false;
        }

        $representativeUser = $this->frontendRepresentativeUserHelper->getRepresentativeUser();

        return false === $this->cookiesAcceptedPropertyHelper->isCookiesAccepted($representativeUser);
    }

    public function getBannerText(): string
    {
        $bannerTexts = $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::PARAM_NAME_LOCALIZED_BANNER_TEXT)
        );

        $localization = $this->localizationHelper->getCurrentLocalization();

        return $this->htmlTagHelper->purify(
            (string)$this->localizedValueExtractor->getLocalizedFallbackValue($bannerTexts, $localization)
        );
    }

    public function isPageExist(): bool
    {
        if ($this->cmsPageLoaded) {
            return null !== $this->cmsPage;
        }

        $localization = $this->localizationHelper->getCurrentLocalization();
        $this->cmsPage = $this->landingPageProvider->getPageDtoByLocalization($localization);
        $this->cmsPageLoaded = true;

        return null !== $this->cmsPage;
    }

    public function getPageTitle(): string
    {
        if (!$this->isPageExist()) {
            return '';
        }

        return $this->htmlTagHelper->purify($this->cmsPage->getTitle());
    }

    public function getPageUrl(): string
    {
        if (!$this->isPageExist()) {
            return '';
        }

        return $this->cmsPage->getUrl();
    }
}
