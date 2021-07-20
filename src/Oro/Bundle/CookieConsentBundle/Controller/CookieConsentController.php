<?php

namespace Oro\Bundle\CookieConsentBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Action\RunActionGroup;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller that process acsept coocies request.
 */
class CookieConsentController extends AbstractController
{
    /**
     * @Route("/cookies-accepted", name="oro_cookie_consent_set_cookies_accepted")
     * @Method({"POST"})
     */
    public function setCookiesAcceptedAction(): Response
    {
        $errors = new ArrayCollection();

        $action = $this->get('oro_action.action.run_action_group');
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
}
