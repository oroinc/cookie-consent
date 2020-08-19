<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Unit\Stubs;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

class CustomerUserStub extends CustomerUser
{
    /** @var bool */
    private $cookiesAccepted;

    /**
     * @param bool $cookiesAccepted
     */
    public function __construct($cookiesAccepted = true)
    {
        $this->cookiesAccepted = $cookiesAccepted;
    }

    /**
     * @return bool
     */
    public function getCookiesAccepted(): bool
    {
        return $this->cookiesAccepted;
    }

    /**
     * @param bool $cookiesAccepted
     */
    public function setCookiesAccepted(bool $cookiesAccepted)
    {
        $this->cookiesAccepted = $cookiesAccepted;
    }
}
