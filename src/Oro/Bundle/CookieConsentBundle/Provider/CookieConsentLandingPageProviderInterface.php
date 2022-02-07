<?php

namespace Oro\Bundle\CookieConsentBundle\Provider;

use Oro\Bundle\CookieConsentBundle\Transformer\DTO\Page;
use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * This interface must be applied to the service that provides information about the Cookie Consent Policy page
 */
interface CookieConsentLandingPageProviderInterface
{
    public function getPageDtoByLocalization(?Localization $localization): ?Page;
}
