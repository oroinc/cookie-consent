<?php

namespace Oro\Bundle\CookieConsentBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CookieConsentBundle\Transformer\DTO\Page;
use Oro\Bundle\CookieConsentBundle\Transformer\PageIdToDtoTransformer;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizedValueExtractor;

/**
 * This service provides information about the Cookie Consent Policy page
 */
class CookieConsentLandingPageProvider implements CookieConsentLandingPageProviderInterface
{
    private array $cache = [];

    public function __construct(
        private readonly ConfigManager $configManager,
        private readonly LocalizedValueExtractor $localizedValueExtractor,
        private readonly PageIdToDtoTransformer $pageIdToDTOTransformer
    ) {
    }

    #[\Override]
    public function getPageDtoByLocalization(?Localization $localization): ?Page
    {
        $localizationId = $localization?->getId();
        if (!\array_key_exists($localizationId, $this->cache)) {
            $landingPageId = $this->localizedValueExtractor->getLocalizedFallbackValue(
                $this->configManager->get('oro_cookie_consent.localized_landing_page_id'),
                $localization
            );
            $this->cache[$localizationId] = $landingPageId
                ? $this->pageIdToDTOTransformer->transform($landingPageId)
                : null;
        }

        return $this->cache[$localizationId];
    }
}
