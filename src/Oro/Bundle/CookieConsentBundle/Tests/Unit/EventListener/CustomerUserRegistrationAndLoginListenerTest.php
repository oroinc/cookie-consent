<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CookieConsentBundle\EventListener\CustomerUserRegistrationAndLoginListener;
use Oro\Bundle\CookieConsentBundle\Helper\CookiesAcceptedPropertyHelper;
use Oro\Bundle\CookieConsentBundle\Helper\FrontendRepresentativeUserHelper;
use Oro\Bundle\CookieConsentBundle\Tests\Unit\Stubs\CustomerUserStub;
use Oro\Bundle\CookieConsentBundle\Tests\Unit\Stubs\CustomerVisitorStub;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitorManager;
use Oro\Bundle\CustomerBundle\Event\FilterCustomerUserResponseEvent;
use Oro\Bundle\CustomerBundle\Security\AnonymousCustomerUserAuthenticator;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class CustomerUserRegistrationAndLoginListenerTest extends TestCase
{
    private const EXIST_SESSION_ID_WITH_COOKIES_ACCEPTED_ID = '05f2ce876de8';
    private const EXIST_SESSION_ID_WITH_COOKIES_NOT_ACCEPTED_ID = '05f2ce876de1';
    private const NOT_EXIST_SESSION_ID = '142a23939af1';

    private TokenStorageInterface&MockObject $tokenStorage;
    private ManagerRegistry&MockObject $doctrine;
    private CustomerUserRegistrationAndLoginListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $visitorManager = $this->createMock(CustomerVisitorManager::class);
        $visitorManager->expects(self::any())
            ->method('find')
            ->willReturnCallback(function ($sessionId) {
                if (self::EXIST_SESSION_ID_WITH_COOKIES_ACCEPTED_ID === $sessionId) {
                    return new CustomerVisitorStub(true);
                }

                if (self::EXIST_SESSION_ID_WITH_COOKIES_NOT_ACCEPTED_ID === $sessionId) {
                    return new CustomerVisitorStub(false);
                }

                return null;
            });

        $this->listener = new CustomerUserRegistrationAndLoginListener(
            new FrontendRepresentativeUserHelper($this->tokenStorage),
            new CookiesAcceptedPropertyHelper(),
            $this->doctrine,
            $visitorManager
        );
    }

    private function createResponseEvent(
        bool $expectsGetUser,
        bool $cookiesAccepted
    ): FilterCustomerUserResponseEvent {
        $event = $this->createMock(FilterCustomerUserResponseEvent::class);

        if ($expectsGetUser) {
            $event->expects(self::exactly(2))
                ->method('getCustomerUser')
                ->willReturn(new CustomerUserStub($cookiesAccepted));
        } else {
            $event->expects(self::once())
                ->method('getCustomerUser')
                ->willReturn(new CustomerUserStub($cookiesAccepted));
        }

        return $event;
    }

    private function createInteractiveLoginEvent(
        UserInterface $user,
        ?array $visitorCredentials
    ): InteractiveLoginEvent {
        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::exactly(2))
            ->method('getUser')
            ->willReturn($user);

        $cookiesData = [];
        if (null !== $visitorCredentials) {
            $cookiesData[AnonymousCustomerUserAuthenticator::COOKIE_NAME] = base64_encode(json_encode(
                $visitorCredentials,
                JSON_THROW_ON_ERROR
            ));
        }

        return new InteractiveLoginEvent(new Request([], [], [], $cookiesData), $token);
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
            $entityManager = $this->createMock(EntityManagerInterface::class);
            $entityManager->expects(self::once())
                ->method('persist');
            $entityManager->expects(self::once())
                ->method('flush');
            $this->doctrine->expects(self::once())
                ->method('getManagerForClass')
                ->with(CustomerUser::class)
                ->willReturn($entityManager);
        }

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturnCallback($tokenCallback);

        $this->listener->onRegistrationCompleted($event);

        /** @var CustomerUserStub $customerUser */
        $customerUser = $event->getCustomerUser();
        self::assertEquals($expectedCookiesAccepted, $customerUser->getCookiesAccepted());
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
                    $token->expects(self::once())
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
                    $token->expects(self::once())
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
                    $token->expects(self::once())
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
                    $token->expects(self::once())
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
                    $token->expects(self::once())
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
            $entityManager = $this->createMock(EntityManagerInterface::class);
            $entityManager->expects(self::once())
                ->method('persist');
            $entityManager->expects(self::once())
                ->method('flush');
            $this->doctrine->expects(self::once())
                ->method('getManagerForClass')
                ->with(CustomerUser::class)
                ->willReturn($entityManager);
        }

        $this->listener->onSecurityInteractiveLogin($event);

        $authToken = $event->getAuthenticationToken();
        self::assertInstanceOf(TokenInterface::class, $authToken);

        $user = $authToken->getUser();
        if ($user instanceof CustomerUser) {
            self::assertEquals($expectedCookiesAccepted, $user->getCookiesAccepted());
        }
    }

    public function interactiveLoginDataProvider(): array
    {
        return [
            'tokenUserIsNotAnObject' => [
                'event' => $this->createInteractiveLoginEvent(
                    $this->createMock(UserInterface::class),
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
            'visitorCredentialsSessionIdIsNull' => [
                'event' => $this->createInteractiveLoginEvent(
                    new CustomerUserStub(false),
                    [1, null]
                ),
                'expectedCookiesAccepted' => false
            ],
            'customerVisitorIsNullNotFound' => [
                'event' => $this->createInteractiveLoginEvent(
                    new CustomerUserStub(false),
                    [1, self::NOT_EXIST_SESSION_ID]
                ),
                'expectedCookiesAccepted' => false
            ],
            'customerVisitorAcceptedIsFalse' => [
                'event' => $this->createInteractiveLoginEvent(
                    new CustomerUserStub(false),
                    [1, self::EXIST_SESSION_ID_WITH_COOKIES_NOT_ACCEPTED_ID]
                ),
                'expectedCookiesAccepted' => false
            ],
            'customerUserAcceptedIsTrue' => [
                'event' => $this->createInteractiveLoginEvent(
                    new CustomerUserStub(true),
                    [1, self::EXIST_SESSION_ID_WITH_COOKIES_NOT_ACCEPTED_ID]
                ),
                'expectedCookiesAccepted' => true
            ],
            'customerVisitorAcceptedIsTrue' => [
                'event' => $this->createInteractiveLoginEvent(
                    new CustomerUserStub(false),
                    [1, self::EXIST_SESSION_ID_WITH_COOKIES_ACCEPTED_ID]
                ),
                'expectedCookiesAccepted' => true,
                'expectedEntityPersist' => true
            ],
        ];
    }
}
