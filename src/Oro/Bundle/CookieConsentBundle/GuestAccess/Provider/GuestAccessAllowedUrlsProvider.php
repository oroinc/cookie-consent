<?php

namespace Oro\Bundle\CookieConsentBundle\GuestAccess\Provider;

use Oro\Bundle\CookieConsentBundle\Layout\DataProvider\CookiesBannerProvider;
use Oro\Bundle\FrontendBundle\GuestAccess\Provider\GuestAccessAllowedUrlsProviderInterface;
use Oro\Component\Routing\UrlUtil;
use Symfony\Component\Routing\RequestContext;

/**
 * Provides a list of patterns for URLs for which access is granted for non-authenticated visitors.
 */
class GuestAccessAllowedUrlsProvider implements GuestAccessAllowedUrlsProviderInterface
{
    /** @var string[] */
    private array $allowedUrls = [];
    private CookiesBannerProvider $cookiesBannerProvider;
    private RequestContext $requestContext;

    public function __construct(
        CookiesBannerProvider $cookiesBannerProvider,
        RequestContext $requestContext
    ) {
        $this->cookiesBannerProvider = $cookiesBannerProvider;
        $this->requestContext = $requestContext;
    }

    /**
     * Adds a pattern to the list of allowed URL patterns.
     */
    public function addAllowedUrlPattern(string $pattern): void
    {
        $this->allowedUrls[] = $pattern;
    }

    public function getAllowedUrlsPatterns()
    {
        if (!$this->cookiesBannerProvider->getPageUrl()) {
            return $this->allowedUrls;
        }

        $baseUrl = $this->requestContext->getBaseUrl();
        $cookiesBannerPageUrl = '^' . UrlUtil::getPathInfo($this->cookiesBannerProvider->getPageUrl(), $baseUrl) . '$';

        return \array_merge(
            $this->allowedUrls,
            [$cookiesBannerPageUrl]
        );
    }
}
