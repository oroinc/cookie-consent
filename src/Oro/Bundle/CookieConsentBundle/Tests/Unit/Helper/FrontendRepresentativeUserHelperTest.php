<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Unit\Helper;

use Oro\Bundle\CookieConsentBundle\Helper\FrontendRepresentativeUserHelper;
use Oro\Bundle\CookieConsentBundle\Tests\Unit\Stubs\CustomerUserStub;
use Oro\Bundle\CookieConsentBundle\Tests\Unit\Stubs\CustomerVisitorStub;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitorManager;
use Oro\Bundle\CustomerBundle\Security\Firewall\AnonymousCustomerUserAuthenticationListener;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class FrontendRepresentativeUserHelperTest extends \PHPUnit\Framework\TestCase
{
    private const EXIST_VISITOR_ID = 99;
    private const EXIST_SESSION_ID = 'aaabbbbyyyy';

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var FrontendRepresentativeUserHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $visitorManager = $this->createMock(CustomerVisitorManager::class);
        $visitorManager->expects($this->any())
            ->method('find')
            ->willReturnCallback(function ($visitorId, $sessionId) {
                if (self::EXIST_VISITOR_ID === $visitorId
                    && self::EXIST_SESSION_ID === $sessionId
                ) {
                    return new CustomerVisitorStub(true);
                }

                return null;
            })
        ;
        $this->helper = new FrontendRepresentativeUserHelper($this->tokenStorage, $visitorManager);
    }

    /**
     * @dataProvider getRepresentativeUserProvider
     */
    public function testGetRepresentativeUser(callable $tokenCallback, ?object $expectedResult)
    {
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturnCallback($tokenCallback);

        $this->assertSame($expectedResult, $this->helper->getRepresentativeUser());
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

    private function getVisitorToken(?CustomerVisitorStub $visitor): AnonymousCustomerUserToken
    {
        $token = $this->createMock(AnonymousCustomerUserToken::class);
        $token->expects($this->once())
            ->method('getVisitor')
            ->willReturn($visitor);
        $token->expects($this->never())
            ->method('getUser');

        return $token;
    }

    private function getUserToken(?object $customerUser): AbstractToken
    {
        $token = $this->createMock(AbstractToken::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($customerUser);

        return $token;
    }

    /**
     * @dataProvider getRepresentativeUserRequestProvider
     */
    public function testGetVisitorFromRequest(Request $request, bool $expectFound)
    {
        $user = $this->helper->getVisitorFromRequest($request);

        self::assertEquals($expectFound, null !== $user);
    }

    private function createRequestWithCookies(?array $visitorCredentials): Request
    {
        $cookiesData = [];
        if (null !== $visitorCredentials) {
            $serializedCredentials = base64_encode(json_encode($visitorCredentials, JSON_THROW_ON_ERROR));
            $cookiesData[AnonymousCustomerUserAuthenticationListener::COOKIE_NAME] = $serializedCredentials;
        }

        return new Request([], [], [], $cookiesData);
    }

    public function getRepresentativeUserRequestProvider(): array
    {
        return [
            'Cookie param not set' => [
                'request' => $this->createRequestWithCookies(null),
                'expectFound' => false
            ],
            'Cookie param empty array' => [
                'request' => $this->createRequestWithCookies([]),
                'expectFound' => false
            ],
            'Cookie param not cocistent' => [
                'request' => $this->createRequestWithCookies([123]),
                'expectFound' => false
            ],
            'Cookie param visitorId is null' => [
                'request' => $this->createRequestWithCookies([null, 'xczabzc']),
                'expectFound' => false
            ],
            'Cookie param sessionId is null' => [
                'request' => $this->createRequestWithCookies([123, null]),
                'expectFound' => false
            ],
            'Cookie param not exist visitor credentials' => [
                'request' => $this->createRequestWithCookies([123, 'xczabzc']),
                'expectFound' => false
            ],
            'Cookie param exist visitor credentials' => [
                'request' => $this->createRequestWithCookies([self::EXIST_VISITOR_ID, self::EXIST_SESSION_ID]),
                'expectFound' => true
            ]
        ];
    }
}
