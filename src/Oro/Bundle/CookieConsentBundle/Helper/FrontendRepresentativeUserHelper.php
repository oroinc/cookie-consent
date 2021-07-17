<?php

namespace Oro\Bundle\CookieConsentBundle\Helper;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitorManager;
use Oro\Bundle\CustomerBundle\Security\Firewall\AnonymousCustomerUserAuthenticationListener;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Helper that returns current user or visitor.
 */
class FrontendRepresentativeUserHelper
{
    /** @var TokenStorageInterface $tokenStorage */
    private $tokenStorage;

    /** @var CustomerVisitorManager */
    private $visitorManager;

    /**
     * FrontendRepresentativeUserHelper constructor.
     */
    public function __construct(TokenStorageInterface $tokenStorage, CustomerVisitorManager $visitorManager)
    {
        $this->tokenStorage = $tokenStorage;
        $this->visitorManager = $visitorManager;
    }

    /**
     * @return null|CustomerVisitor|CustomerUser
     */
    public function getRepresentativeUser()
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return null;
        }

        if ($token instanceof AnonymousCustomerUserToken) {
            return $token->getVisitor();
        }

        $user = $token->getUser();
        return $user instanceof CustomerUser ? $user : null;
    }

    public function getVisitorFromRequest(Request $request): ?CustomerVisitor
    {
        $value = $request->cookies->get(AnonymousCustomerUserAuthenticationListener::COOKIE_NAME);
        if (!$value) {
            return null;
        }

        $unserialized = \json_decode(\base64_decode($value));
        if (false === \is_array($unserialized) || 2 > \count($unserialized)) {
            return null;
        }

        list($visitorId, $sessionId) = $unserialized;

        return ($visitorId && $sessionId)
            ? $this->visitorManager->find($visitorId, $sessionId)
            : null
        ;
    }
}
