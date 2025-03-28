<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Unit\Helper;

use Oro\Bundle\CookieConsentBundle\Helper\FrontendRepresentativeUserHelper;
use Oro\Bundle\CookieConsentBundle\Tests\Unit\Stubs\CustomerUserStub;
use Oro\Bundle\CookieConsentBundle\Tests\Unit\Stubs\CustomerVisitorStub;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class FrontendRepresentativeUserHelperTest extends TestCase
{
    private TokenStorageInterface&MockObject $tokenStorage;
    private FrontendRepresentativeUserHelper $helper;

    #[\Override]
    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->helper = new FrontendRepresentativeUserHelper($this->tokenStorage);
    }

    private function getVisitorToken(?CustomerVisitorStub $visitor): AnonymousCustomerUserToken
    {
        $token = $this->createMock(AnonymousCustomerUserToken::class);
        $token->expects(self::once())
            ->method('getVisitor')
            ->willReturn($visitor);
        $token->expects(self::never())
            ->method('getUser');

        return $token;
    }

    private function getUserToken(?object $customerUser): AbstractToken
    {
        $token = $this->createMock(AbstractToken::class);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        return $token;
    }

    /**
     * @dataProvider getRepresentativeUserProvider
     */
    public function testGetRepresentativeUser(callable $tokenCallback, ?object $expectedResult): void
    {
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturnCallback($tokenCallback);

        self::assertSame($expectedResult, $this->helper->getRepresentativeUser());
    }

    public function getRepresentativeUserProvider(): array
    {
        $customerVisitor = new CustomerVisitorStub();
        $customerUser = new CustomerUserStub();

        return [
            'Token is empty' => [
                'tokenCallback' => function () {
                    return null;
                },
                'expectedResult' => null
            ],
            'Anonymous Token and not contains Visitor' => [
                'tokenCallback' => function () {
                    return $this->getVisitorToken(null);
                },
                'expectedResult' => null
            ],
            'Anonymous Token that contains Visitor' => [
                'tokenCallback' => function () use ($customerVisitor) {
                    return $this->getVisitorToken($customerVisitor);
                },
                'expectedResult' => $customerVisitor
            ],
            'Anonymous Token that contains CustomerUser and cookies accepted' => [
                'tokenCallback' => function () use ($customerVisitor, $customerUser) {
                    $customerVisitor->setCustomerUser($customerUser);
                    $customerVisitor->setCookiesAccepted(false);

                    return $this->getVisitorToken($customerVisitor);
                },
                'expectedResult' => $customerVisitor
            ],
            'Token and not contains CustomerUser' => [
                'tokenCallback' => function () {
                    return $this->getUserToken(null);
                },
                'expectedResult' => null
            ],
            'Token that contains CustomerUser' => [
                'tokenCallback' => function () use ($customerUser) {
                    return $this->getUserToken($customerUser);
                },
                'expectedResult' => $customerUser
            ],
            'Token that contains User' => [
                'tokenCallback' => function () {
                    return $this->getUserToken(new User());
                },
                'expectedResult' => null
            ],
        ];
    }
}
