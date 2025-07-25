<?php

namespace Oro\Bundle\CookieConsentBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Action\RunActionGroup;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller that process accept cookies request.
 */
class CookieConsentController extends AbstractController
{
    #[Route(path: '/cookies-accepted', name: 'oro_cookie_consent_set_cookies_accepted', methods: ['POST'])]
    public function setCookiesAcceptedAction(): Response
    {
        $errors = new ArrayCollection();

        $action = $this->container->get(RunActionGroup::class);
        $action->initialize(
            [
                'action_group' => 'oro_cookie_consent_set_accepted_cookies',
            ]
        );
        $action->execute(
            [
                RunActionGroup::ERRORS_DEFAULT_KEY => $errors,
            ]
        );

        $response = [
            'success' => 0 === $errors->count(),
        ];

        return new JsonResponse($response);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                RunActionGroup::class,
            ]
        );
    }
}
