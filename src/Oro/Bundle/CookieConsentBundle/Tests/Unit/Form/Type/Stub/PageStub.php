<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Unit\Form\Type\Stub;

// @codingStandardsIgnoreStart
class PageStub extends \Oro\Bundle\CMSBundle\Entity\Page
{
    /** @var string */
    private $defaultTitle;

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
