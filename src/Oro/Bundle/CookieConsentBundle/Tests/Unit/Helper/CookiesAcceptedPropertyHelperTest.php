<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Unit\Helper;

use Oro\Bundle\CookieConsentBundle\Helper\CookiesAcceptedPropertyHelper;
use Oro\Bundle\CookieConsentBundle\Tests\Unit\Stubs\CustomerUserStub;
use Oro\Bundle\CookieConsentBundle\Tests\Unit\Stubs\CustomerVisitorStub;
use Oro\Bundle\UserBundle\Entity\User;

class CookiesAcceptedPropertyHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var CookiesAcceptedPropertyHelper */
    private $cookiesAcceptedPropertyHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->cookiesAcceptedPropertyHelper = new CookiesAcceptedPropertyHelper();
    }

    /**
     * @dataProvider isCookiesAcceptedProvider
     *
     * @param object|null $frontendRepresentativeUser
     * @param bool $expectedResult
     */
    public function testIsCookiesAccepted($frontendRepresentativeUser, bool $expectedResult)
    {
        $this->assertEquals(
            $expectedResult,
            $this->cookiesAcceptedPropertyHelper->isCookiesAccepted($frontendRepresentativeUser)
        );
    }

    /**
     * @return array
     */
    public function isCookiesAcceptedProvider()
    {
        return [
            'Frontend representative user is null' => [
                'frontendRepresentativeUser' => null,
                'expectedResult' => false
            ],
            'Frontend representative user is CustomerVisitor with cookies accepted false' => [
                'frontendRepresentativeUser' => new CustomerVisitorStub(false),
                'expectedResult' => false
            ],
            'Frontend representative user is CustomerVisitor with cookies accepted true' => [
                'frontendRepresentativeUser' => new CustomerVisitorStub(true),
                'expectedResult' => true
            ],
            'Frontend representative user is CustomerUser with cookies accepted false' => [
                'frontendRepresentativeUser' => new CustomerUserStub(false),
                'expectedResult' => false
            ],
            'Frontend representative user is CustomerUser with cookies accepted true' => [
                'frontendRepresentativeUser' => new CustomerUserStub(true),
                'expectedResult' => true
            ],
        ];
    }

    public function testIsCookiesAcceptedWithInvalidObject()
    {
        $this->expectException(\LogicException::class);
        $this->cookiesAcceptedPropertyHelper->isCookiesAccepted(new User());
    }

    /**
     * @dataProvider setCookiesAcceptedProvider
     *
     * @param object|null $frontendRepresentativeUser
     * @param bool $cookiesAcceptedValue
     */
    public function testSetCookiesAccepted($frontendRepresentativeUser, bool $cookiesAcceptedValue)
    {
        $this->cookiesAcceptedPropertyHelper->setCookiesAccepted(
            $frontendRepresentativeUser,
            $cookiesAcceptedValue
        );

        $this->assertEquals(
            $cookiesAcceptedValue,
            $frontendRepresentativeUser->getCookiesAccepted()
        );
    }

    /**
     * @return array
     */
    public function setCookiesAcceptedProvider()
    {
        return [
            'Frontend representative user is CustomerVisitor with cookies accepted false' => [
                'frontendRepresentativeUser' => new CustomerVisitorStub(false),
                'cookiesAcceptedValue' => true
            ],
            'Frontend representative user is CustomerVisitor with cookies accepted true' => [
                'frontendRepresentativeUser' => new CustomerVisitorStub(true),
                'cookiesAcceptedValue' => false
            ],
            'Frontend representative user is CustomerUser with cookies accepted false' => [
                'frontendRepresentativeUser' => new CustomerUserStub(false),
                'cookiesAcceptedValue' => true
            ],
            'Frontend representative user is CustomerUser with cookies accepted true' => [
                'frontendRepresentativeUser' => new CustomerUserStub(true),
                'cookiesAcceptedValue' => false
            ],
        ];
    }

    public function testSetCookiesAcceptedOnNullNotCallException()
    {
        $this->cookiesAcceptedPropertyHelper->setCookiesAccepted(
            null,
            true
        );
    }

    public function testSetCookiesAcceptedWithInvalidObject()
    {
        $this->expectException(\LogicException::class);
        $this->cookiesAcceptedPropertyHelper->setCookiesAccepted(new User(), true);
    }
}
