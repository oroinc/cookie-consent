<?php

namespace Oro\Bundle\CookieConsentBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CookieConsentBundle\DependencyInjection\Configuration;
use Oro\Bundle\CookieConsentBundle\Helper\LocalizedValueExtractor;
use Oro\Bundle\CookieConsentBundle\Transformer\DTO\Page;
use Oro\Bundle\CookieConsentBundle\Transformer\PageIdToDtoTransformer;
use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * This service provides information about the Cookie Consent Policy page
 */
class CookieConsentLandingPageProvider implements CookieConsentLandingPageProviderInterface
{
    private array $cache = [];
    private ConfigManager $configManager;
    private LocalizedValueExtractor $localizedValueExtractor;
    private PageIdToDtoTransformer $pageIdToDTOTransformer;

    public function __construct(
        ConfigManager $configManager,
        LocalizedValueExtractor $localizedValueExtractor,
        PageIdToDtoTransformer $pageIdToDTOTransformer
    ) {
        $this->configManager = $configManager;
        $this->localizedValueExtractor = $localizedValueExtractor;
        $this->pageIdToDTOTransformer = $pageIdToDTOTransformer;
    }

    public function getPageDtoByLocalization(?Localization $localization): ?Page
    {
        $localizationId = $localization?->getId();
        if (isset($this->cache[$localizationId])) {
            return $this->cache[$localizationId];
        }

        $landingPageIds = $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::PARAM_NAME_LOCALIZED_LANDING_PAGE_ID)
        );

        $landingPageId = $this->localizedValueExtractor->getLocalizedFallbackValue($landingPageIds, $localization);
        if (!$landingPageId) {
            $this->cache[$localizationId] = null;
        } else {
            $this->cache[$localizationId] = $this->pageIdToDTOTransformer->transform($landingPageId);
        }

        return $this->cache[$localizationId];
    }
}
