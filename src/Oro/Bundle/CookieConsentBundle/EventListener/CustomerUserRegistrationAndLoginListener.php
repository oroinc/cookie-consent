<?php

namespace Oro\Bundle\CookieConsentBundle\EventListener;

use Oro\Bundle\CookieConsentBundle\Helper\CookiesAcceptedPropertyHelper;
use Oro\Bundle\CookieConsentBundle\Helper\FrontendRepresentativeUserHelper;
use Oro\Bundle\CustomerBundle\CustomerUserEvents;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Event\FilterCustomerUserResponseEvent;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * Transfers "cookies_accepted" parameter if it is set for CustomerVisitor
 * who currently registers
 */
class CustomerUserRegistrationAndLoginListener implements EventSubscriberInterface
{
    /** @var FrontendRepresentativeUserHelper */
    private $frontendUserHelper;

    /** @var CookiesAcceptedPropertyHelper */
    private $cookiesAcceptedHelper;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    public function __construct(
        FrontendRepresentativeUserHelper $frontendUserHelper,
        CookiesAcceptedPropertyHelper $cookiesAcceptedHelper,
        DoctrineHelper $doctrineHelper
    ) {
        $this->frontendUserHelper = $frontendUserHelper;
        $this->cookiesAcceptedHelper = $cookiesAcceptedHelper;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CustomerUserEvents::REGISTRATION_COMPLETED => 'onRegistrationCompleted',
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin'
        ];
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    private function transferCookieAccepted(CustomerVisitor $customerVisitor, CustomerUser $customerUser): void
    {
        if (false === $this->cookiesAcceptedHelper->isCookiesAccepted($customerVisitor)) {
            return;
        }

        if (true === $this->cookiesAcceptedHelper->isCookiesAccepted($customerUser)) {
            return;
        }

        $this->cookiesAcceptedHelper->setCookiesAccepted($customerUser, true);
        $entityManager = $this->doctrineHelper->getEntityManagerForClass(CustomerUser::class);
        $entityManager->persist($customerUser);
        $entityManager->flush();
    }

    public function onRegistrationCompleted(FilterCustomerUserResponseEvent $event): void
    {
        $frontendUser = $this->frontendUserHelper->getRepresentativeUser();
        if ($frontendUser instanceof CustomerVisitor) {
            $this->transferCookieAccepted($frontendUser, $event->getCustomerUser());
        }
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        if ($user instanceof CustomerUser) {
            $customerVisitor = $this->frontendUserHelper->getVisitorFromRequest($event->getRequest());
            if (null !== $customerVisitor) {
                $this->transferCookieAccepted($customerVisitor, $user);
            }
        }
    }
}
