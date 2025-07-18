<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Unit\Helper;

use Oro\Bundle\CookieConsentBundle\Helper\CookiesAcceptedPropertyHelper;
use Oro\Bundle\CookieConsentBundle\Tests\Unit\Stubs\CustomerUserStub;
use Oro\Bundle\CookieConsentBundle\Tests\Unit\Stubs\CustomerVisitorStub;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\TestCase;

class CookiesAcceptedPropertyHelperTest extends TestCase
{
    private CookiesAcceptedPropertyHelper $cookiesAcceptedPropertyHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->cookiesAcceptedPropertyHelper = new CookiesAcceptedPropertyHelper();
    }

    /**
     * @dataProvider isCookiesAcceptedProvider
     */
    public function testIsCookiesAccepted(?object $frontendRepresentativeUser, bool $expectedResult): void
    {
        $this->assertEquals(
            $expectedResult,
            $this->cookiesAcceptedPropertyHelper->isCookiesAccepted($frontendRepresentativeUser)
        );
    }

    public function isCookiesAcceptedProvider(): array
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

    public function testIsCookiesAcceptedWithInvalidObject(): void
    {
        $this->expectException(\LogicException::class);
        $this->cookiesAcceptedPropertyHelper->isCookiesAccepted(new User());
    }

    /**
     * @dataProvider setCookiesAcceptedProvider
     */
    public function testSetCookiesAccepted(?object $frontendRepresentativeUser, bool $cookiesAcceptedValue): void
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

    public function setCookiesAcceptedProvider(): array
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

    public function testSetCookiesAcceptedOnNullNotCallException(): void
    {
        $this->cookiesAcceptedPropertyHelper->setCookiesAccepted(
            null,
            true
        );
    }

    public function testSetCookiesAcceptedWithInvalidObject(): void
    {
        $this->expectException(\LogicException::class);
        $this->cookiesAcceptedPropertyHelper->setCookiesAccepted(new User(), true);
    }
}
