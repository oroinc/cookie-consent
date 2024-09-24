<?php

namespace Oro\Bundle\CookieConsentBundle\Migrations\Data\ORM;

use Oro\Bundle\CMSBundle\Migrations\Data\AbstractLoadPageData;

/**
 * Loads default cookie policy page.
 */
class LoadCookieConsentPage extends AbstractLoadPageData
{
    public const TITLE = 'Cookie Policy';

    #[\Override]
    protected function getFilePaths(): string
    {
        return $this->getFilePathsFromLocator(
            '@OroCookieConsentBundle/Migrations/Data/ORM/data/cookie_policy_page.yml'
        );
    }
}
