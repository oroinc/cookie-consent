<?php

namespace Oro\Bundle\CookieConsentBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CookieConsentBundle\Helper\CookiesAcceptedPropertyHelper;
use Oro\Bundle\CookieConsentBundle\Helper\FrontendRepresentativeUserHelper;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitorManager;
use Oro\Bundle\CustomerBundle\Event\FilterCustomerUserResponseEvent;
use Oro\Bundle\CustomerBundle\Security\AnonymousCustomerUserAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * Transfers "cookies_accepted" parameter if it is set for CustomerVisitor
 * who currently registers
 */
class CustomerUserRegistrationAndLoginListener
{
    public function __construct(
        private FrontendRepresentativeUserHelper $frontendRepresentativeUserHelper,
        private CookiesAcceptedPropertyHelper $cookiesAcceptedHelper,
        private ManagerRegistry $doctrine,
        private CustomerVisitorManager $visitorManager
    ) {
    }

    public function onRegistrationCompleted(FilterCustomerUserResponseEvent $event): void
    {
        $frontendUser = $this->frontendRepresentativeUserHelper->getRepresentativeUser();
        if ($frontendUser instanceof CustomerVisitor) {
            $this->transferCookieAccepted($frontendUser, $event->getCustomerUser());
        }
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        if ($user instanceof CustomerUser) {
            $visitor = $this->getVisitorFromCookie($event->getRequest());
            if (null !== $visitor) {
                $this->transferCookieAccepted($visitor, $user);
            }
        }
    }

    private function getVisitorFromCookie(Request $request): ?CustomerVisitor
    {
        $value = $request->cookies->get(AnonymousCustomerUserAuthenticator::COOKIE_NAME);
        if (!$value) {
            return null;
        }

        $sessionId = json_decode(base64_decode($value), null, 512, JSON_THROW_ON_ERROR);
        if (\is_array($sessionId) && isset($sessionId[1])) {
            // BC compatibility (can be removed in v7.0): get sessionId from old format of the cookie value
            $sessionId = $sessionId[1];
        }
        if (!\is_string($sessionId) || !$sessionId) {
            return null;
        }

        return $this->visitorManager->find($sessionId);
    }

    private function transferCookieAccepted(CustomerVisitor $visitor, CustomerUser $customerUser): void
    {
        if (!$this->cookiesAcceptedHelper->isCookiesAccepted($visitor)) {
            return;
        }

        if ($this->cookiesAcceptedHelper->isCookiesAccepted($customerUser)) {
            return;
        }

        $this->cookiesAcceptedHelper->setCookiesAccepted($customerUser, true);
        $entityManager = $this->doctrine->getManagerForClass(CustomerUser::class);
        $entityManager->persist($customerUser);
        $entityManager->flush();
    }
}
