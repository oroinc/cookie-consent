<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\CMSBundle\Entity\Page;

// @codingStandardsIgnoreStart
class PageStub extends Page
{
    private string $defaultTitle;

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setDefaultTitle($title)
    {
        $this->defaultTitle = $title;

        return $this;
    }

    /**
     * @return \Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue|string
     */
    public function getDefaultTitle()
    {
        return $this->defaultTitle;
    }
}
// @codingStandardsIgnoreEnd
