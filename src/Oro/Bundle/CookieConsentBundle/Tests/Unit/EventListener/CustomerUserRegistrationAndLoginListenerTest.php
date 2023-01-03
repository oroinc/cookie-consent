<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CookieConsentBundle\EventListener\CustomerUserRegistrationAndLoginListener;
use Oro\Bundle\CookieConsentBundle\Helper\CookiesAcceptedPropertyHelper;
use Oro\Bundle\CookieConsentBundle\Helper\FrontendRepresentativeUserHelper;
use Oro\Bundle\CookieConsentBundle\Tests\Unit\Stubs\CustomerUserStub;
use Oro\Bundle\CookieConsentBundle\Tests\Unit\Stubs\CustomerVisitorStub;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitorManager;
use Oro\Bundle\CustomerBundle\Event\FilterCustomerUserResponseEvent;
use Oro\Bundle\CustomerBundle\Security\Firewall\AnonymousCustomerUserAuthenticationListener;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class CustomerUserRegistrationAndLoginListenerTest extends \PHPUnit\Framework\TestCase
{
    private const EXIST_VISITOR_WITH_COOKIES_ACCEPTED_ID = 99;
    private const EXIST_VISITOR_WITH_COOKIES_NOT_ACCEPTED_ID = 100;

    private const EXIST_SESSION_ID = '05f2ce876de8';
    private const NOT_EXIST_SESSION_ID = '142a23939af1';

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var CustomerUserRegistrationAndLoginListener */
    private $eventHandler;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $visitorManager = $this->createMock(CustomerVisitorManager::class);
        $visitorManager->expects(self::any())
            ->method('find')
            ->willReturnCallback(function ($visitorId, $sessionId) {
                if (self::EXIST_VISITOR_WITH_COOKIES_ACCEPTED_ID === $visitorId
                    && self::EXIST_SESSION_ID === $sessionId
                ) {
                    return new CustomerVisitorStub(true);
                }

                if (self::EXIST_VISITOR_WITH_COOKIES_NOT_ACCEPTED_ID === $visitorId
                    && self::EXIST_SESSION_ID === $sessionId
                ) {
                    return new CustomerVisitorStub(false);
                }

                return null;
            })
        ;
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->eventHandler = new CustomerUserRegistrationAndLoginListener(
            new FrontendRepresentativeUserHelper($this->tokenStorage, $visitorManager),
            new CookiesAcceptedPropertyHelper(),
            $this->doctrineHelper
        );
    }

    /**
     * @dataProvider registrationCompletedDataProvider
     */
    public function testRegistrationCompleted(
        callable $tokenCallback,
        FilterCustomerUserResponseEvent $event,
        bool $expectedCookiesAccepted,
        bool $expectedEntityPersist = false
    ): void {
        if ($expectedEntityPersist) {
            $entityManager = $this->createMock(EntityManager::class);
            $entityManager->expects(self::once())
                ->method('persist');
            $entityManager->expects(self::once())
                ->method('flush');
            $this->doctrineHelper->expects(self::once())
                ->method('getEntityManagerForClass')
                ->with(CustomerUser::class)
                ->willReturn($entityManager);
        }

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturnCallback($tokenCallback);
        $this->eventHandler->onRegistrationCompleted($event);

        /** @var CustomerUserStub $customerUser */
        $customerUser = $event->getCustomerUser();
        self::assertEquals($expectedCookiesAccepted, $customerUser->getCookiesAccepted());
    }

    private function createResponseEvent(
        bool $expectsGetUser,
        bool $cookiesAccepted
    ): \PHPUnit\Framework\MockObject\MockObject|FilterCustomerUserResponseEvent {
        $event = $this->createMock(FilterCustomerUserResponseEvent::class);

        if ($expectsGetUser) {
            $event->expects(self::exactly(2))
                ->method('getCustomerUser')
                ->willReturn(new CustomerUserStub($cookiesAccepted));
        } else {
            // will only be called before assertEquals in testRegistrationCompleted
            $event->expects(self::once())
                ->method('getCustomerUser')
                ->willReturn(new CustomerUserStub($cookiesAccepted));
        }

        return $event;
    }

    public function registrationCompletedDataProvider(): array
    {
        return [
            'tokenNotAnObject' => [
                'token' => function () {
                    return null;
                },
                'customerUser' => $this->createResponseEvent(false, false),
                'expectedCookiesAccepted' => false,
            ],
            'tokenNotInstanceOfAnonymousCustomerUserToken' => [
                'token' => function () {
                    return $this->createMock(TokenInterface::class);
                },
                'customerUser' => $this->createResponseEvent(false, false),
                'expectedCookiesAccepted' => false,
            ],
            'tokenCustomerVisitorIsNull' => [
                'token' => function () {
                    $token = $this->createMock(AnonymousCustomerUserToken::class);
                    $token->expects($this->once())
                        ->method('getVisitor')
                        ->willReturn(null);

                    return $token;
                },
                'customerUser' => $this->createResponseEvent(false, false),
                'expectedCookiesAccepted' => false,
            ],
            'customerUserCookiesAcceptedIsTrue' => [
                'token' => function () {
                    $visitor = new CustomerVisitorStub(false);
                    $token = $this->createMock(AnonymousCustomerUserToken::class);
                    $token->expects($this->once())
                        ->method('getVisitor')
                        ->willReturn($visitor);

                    return $token;
                },
                'customerUser' => $this->createResponseEvent(true, true),
                'expectedCookiesAccepted' => true,
            ],
            'customerUserAndCustomerVisitorAcceptedIsFalse' => [
                'token' => function () {
                    $visitor = new CustomerVisitorStub(false);
                    $token = $this->createMock(AnonymousCustomerUserToken::class);
                    $token->expects($this->once())
                        ->method('getVisitor')
                        ->willReturn($visitor);

                    return $token;
                },
                'customerUser' => $this->createResponseEvent(true, false),
                'expectedCookiesAccepted' => false,
            ],
            'customerUserAndCustomerVisitorAcceptedIsTrue' => [
                'token' => function () {
                    $visitor = new CustomerVisitorStub(true);
                    $token = $this->createMock(AnonymousCustomerUserToken::class);
                    $token->expects($this->once())
                        ->method('getVisitor')
                        ->willReturn($visitor);

                    return $token;
                },
                'customerUser' => $this->createResponseEvent(true, true),
                'expectedCookiesAccepted' => true,
            ],
            'customerVisitorCookiesAcceptedIsTrue' => [
                'token' => function () {
                    $visitor = new CustomerVisitorStub(true);
                    $token = $this->createMock(AnonymousCustomerUserToken::class);
                    $token->expects($this->once())
                        ->method('getVisitor')
                        ->willReturn($visitor);

                    return $token;
                },
                'customerUser' => $this->createResponseEvent(true, false),
                'expectedCookiesAccepted' => true,
                'expectedEntityPersist' => true,
            ],
        ];
    }

    /**
     * @dataProvider interactiveLoginDataProvider
     */
    public function testInteractiveLogin(
        InteractiveLoginEvent $event,
        bool $expectedCookiesAccepted,
        bool $expectedEntityPersist = false
    ): void {
        if ($expectedEntityPersist) {
            $entityManager = $this->createMock(EntityManager::class);
            $entityManager->expects(self::once())
                ->method('persist');
            $entityManager->expects(self::once())
                ->method('flush');
            $this->doctrineHelper->expects(self::once())
                ->method('getEntityManagerForClass')
                ->with(CustomerUser::class)
                ->willReturn($entityManager);
        }

        $this->eventHandler->onSecurityInteractiveLogin($event);

        $authToken = $event->getAuthenticationToken();
        self::assertInstanceOf(TokenInterface::class, $authToken);

        $user = $authToken->getUser();
        if ($user instanceof CustomerUser) {
            self::assertEquals($expectedCookiesAccepted, $user->getCookiesAccepted());
        }
    }

    private function createInteractiveLoginEvent(
        UserInterface|string $user,
        ?array $visitorCredentials
    ): InteractiveLoginEvent|\PHPUnit\Framework\MockObject\MockObject {
        $tokenMock = $this->createMock(TokenInterface::class);
        $tokenMock->expects(self::exactly(2))
            ->method('getUser')
            ->willReturn($user);

        $cookiesData = [];
        if (null !== $visitorCredentials) {
            $serializedCredentials = base64_encode(json_encode($visitorCredentials, JSON_THROW_ON_ERROR));
            $cookiesData[AnonymousCustomerUserAuthenticationListener::COOKIE_NAME] = $serializedCredentials;
        }

        $request = new Request([], [], [], $cookiesData);

        return new InteractiveLoginEvent($request, $tokenMock);
    }

    public function interactiveLoginDataProvider(): array
    {
        return [
            'tokenUserIsNotAnObject' => [
                'event' => $this->createInteractiveLoginEvent(
                    'ANONYMOUS_USER',
                    []
                ),
                'expectedCookiesAccepted' => false
            ],
            'tokenUserIsNotInstanceOfCustomerUser' => [
                'event' => $this->createInteractiveLoginEvent(
                    $this->createMock(UserInterface::class),
                    []
                ),
                'expectedCookiesAccepted' => false
            ],
            'visitorCredentialsCookieNotSet' => [
                'event' => $this->createInteractiveLoginEvent(
                    new CustomerUserStub(false),
                    null
                ),
                'expectedCookiesAccepted' => false
            ],
            'visitorCredentialsVisitorIdIsNull' => [
                'event' => $this->createInteractiveLoginEvent(
                    new CustomerUserStub(false),
                    [null, self::EXIST_SESSION_ID]
                ),
                'expectedCookiesAccepted' => false
            ],
            'visitorCredentialsSessionIdIsNull' => [
                'event' => $this->createInteractiveLoginEvent(
                    new CustomerUserStub(false),
                    [self::EXIST_VISITOR_WITH_COOKIES_NOT_ACCEPTED_ID, null]
                ),
                'expectedCookiesAccepted' => false
            ],
            'customerVisitorIsNullNotFound' => [
                'event' => $this->createInteractiveLoginEvent(
                    new CustomerUserStub(false),
                    [self::EXIST_VISITOR_WITH_COOKIES_NOT_ACCEPTED_ID, self::NOT_EXIST_SESSION_ID]
                ),
                'expectedCookiesAccepted' => false
            ],
            'customerVisitorAcceptedIsFalse' => [
                'event' => $this->createInteractiveLoginEvent(
                    new CustomerUserStub(false),
                    [self::EXIST_VISITOR_WITH_COOKIES_NOT_ACCEPTED_ID, self::EXIST_SESSION_ID]
                ),
                'expectedCookiesAccepted' => false
            ],
            'customerUserAcceptedIsTrue' => [
                'event' => $this->createInteractiveLoginEvent(
                    new CustomerUserStub(true),
                    [self::EXIST_VISITOR_WITH_COOKIES_NOT_ACCEPTED_ID, self::EXIST_SESSION_ID]
                ),
                'expectedCookiesAccepted' => true
            ],
            'customerVisitorAcceptedIsTrue' => [
                'event' => $this->createInteractiveLoginEvent(
                    new CustomerUserStub(false),
                    [self::EXIST_VISITOR_WITH_COOKIES_ACCEPTED_ID, self::EXIST_SESSION_ID]
                ),
                'expectedCookiesAccepted' => true,
                'expectedEntityPersist' => true
            ],
        ];
    }
}
