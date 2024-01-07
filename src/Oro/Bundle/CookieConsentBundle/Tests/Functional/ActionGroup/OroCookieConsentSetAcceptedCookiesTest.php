<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Functional\ActionGroup;

use Oro\Bundle\CookieConsentBundle\Tests\Functional\DataFixtures\LoadVisitorsData;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

/**
 * @dbIsolationPerTest
 */
class OroCookieConsentSetAcceptedCookiesTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadVisitorsData::class, LoadOrganization::class]);
    }

    public function testRunActionGroupWithCustomerUserAsUser()
    {
        /** @var CustomerUser $user */
        $user = self::getContainer()->get('doctrine')
            ->getRepository(CustomerUser::class)
            ->findOneBy(['username' => LoadCustomerUserData::AUTH_USER]);

        self::assertFalse($user->getCookiesAccepted());

        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordOrganizationToken(
            $user,
            'key',
            $user->getOrganization(),
            $user->getUserRoles()
        ));

        $action = $this->client->getContainer()->get('oro_action.action.run_action_group');
        $action->initialize([
            'action_group' => 'oro_cookie_consent_set_accepted_cookies'
        ]);
        $action->execute([]);

        self::assertTrue($user->getCookiesAccepted());
    }

    public function testRunActionGroupAndAnonymousToken()
    {
        $customerVisitor = $this->getReference(LoadVisitorsData::CUSTOMER_VISITOR);

        self::getContainer()->get('security.token_storage')->setToken(new AnonymousCustomerUserToken(
            $customerVisitor,
            [],
            $this->getReference(LoadOrganization::ORGANIZATION)
        ));

        $action = $this->client->getContainer()->get('oro_action.action.run_action_group');
        $action->initialize([
            'action_group' => 'oro_cookie_consent_set_accepted_cookies'
        ]);
        $action->execute([]);

        self::assertTrue($customerVisitor->getCookiesAccepted());
    }
}
