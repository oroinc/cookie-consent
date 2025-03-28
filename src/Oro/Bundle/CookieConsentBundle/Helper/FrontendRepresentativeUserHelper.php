<?php

namespace Oro\Bundle\CookieConsentBundle\Helper;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Helper that returns current user or visitor.
 */
class FrontendRepresentativeUserHelper
{
    public function __construct(
        private TokenStorageInterface $tokenStorage
    ) {
    }

    public function getRepresentativeUser(): null|CustomerVisitor|CustomerUser
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
}
