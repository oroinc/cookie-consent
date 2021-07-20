<?php

namespace Oro\Bundle\CookieConsentBundle\Transformer\DTO;

use Oro\Component\Action\Model\AbstractStorage;

/**
 * Contains information about resolved title and url
 */
class Page extends AbstractStorage
{
    /**
     * @param string $pageTitle
     * @param string $pageUrl
     *
     * @return Page
     */
    public static function create(string $pageTitle, string $pageUrl) : self
    {
        return new static([
            'pageTitle' => $pageTitle,
            'pageUrl' => $pageUrl
        ]);
    }

    public function getTitle(): string
    {
        return $this->get('pageTitle');
    }

    public function getUrl(): string
    {
        return $this->get('pageUrl');
    }
}
