<?php

namespace Oro\Bundle\CookieConsentBundle\GuestAccess\Provider;

use Oro\Bundle\CookieConsentBundle\Provider\CookieConsentLandingPageProviderInterface;
use Oro\Bundle\FrontendBundle\GuestAccess\Provider\GuestAccessAllowedUrlsProviderInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Component\Routing\UrlUtil;
use Symfony\Component\Routing\RequestContext;

/**
 * Provides a list of patterns for URLs for which access is granted for non-authenticated visitors.
 */
class GuestAccessAllowedUrlsProvider implements GuestAccessAllowedUrlsProviderInterface
{
    /** @var string[] */
    private array $allowedUrls = [];
    private CookieConsentLandingPageProviderInterface $landingPageProvider;
    private LocalizationHelper $localizationHelper;
    private RequestContext $requestContext;

    public function __construct(
        CookieConsentLandingPageProviderInterface $landingPageProvider,
        LocalizationHelper $localizationHelper,
        RequestContext $requestContext
    ) {
        $this->landingPageProvider = $landingPageProvider;
        $this->localizationHelper = $localizationHelper;
        $this->requestContext = $requestContext;
    }

    /**
     * Adds a pattern to the list of allowed URL patterns.
     */
    public function addAllowedUrlPattern(string $pattern): void
    {
        $this->allowedUrls[] = $pattern;
    }

    public function getAllowedUrlsPatterns(): array
    {
        $localization = $this->localizationHelper->getCurrentLocalization();
        $cookieConsentLandingPageDto = $this->landingPageProvider->getPageDtoByLocalization($localization);
        if (null === $cookieConsentLandingPageDto) {
            return $this->allowedUrls;
        }

        $baseUrl = $this->requestContext->getBaseUrl();
        $cookiesBannerPageUrl = '^' . UrlUtil::getPathInfo($cookieConsentLandingPageDto->getUrl(), $baseUrl) . '$';

        return \array_merge(
            $this->allowedUrls,
            [$cookiesBannerPageUrl]
        );
    }
}
