<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Functional\ActionGroup;

use Oro\Bundle\CookieConsentBundle\Tests\Functional\DataFixtures\LoadVisitorsData;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @dbIsolationPerTest
 */
class OroCookieConsentSetAcceptedCookiesTest extends WebTestCase
{
    use EntityTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadVisitorsData::class
        ]);
    }

    public function testRunActionGroupWithCustomerUserAsUser()
    {
        $registry = $this->getContainer()->get('doctrine');
        $user = $registry
            ->getRepository(CustomerUser::class)
            ->findOneBy(['username' => LoadCustomerUserData::AUTH_USER]);
        $organization = $registry->getRepository(Organization::class)->getFirst();

        $this->assertFalse($user->getCookiesAccepted());

        $token = new UsernamePasswordOrganizationToken($user, false, 'key', $organization, $user->getRoles());
        $this->client->getContainer()->get('security.token_storage')->setToken($token);

        $action = $this->client->getContainer()->get('oro_action.action.run_action_group');
        $action->initialize([
            'action_group' => 'oro_cookie_consent_set_accepted_cookies'
        ]);
        $action->execute([]);

        $this->assertTrue($user->getCookiesAccepted());
    }

    public function testRunActionGroupAndAnonymousToken()
    {
        $customerVisitor = $this->getReference(LoadVisitorsData::CUSTOMER_VISITOR);

        $registry = $this->getContainer()->get('doctrine');
        $organization = $registry->getRepository(Organization::class)->getFirst();

        $token = new AnonymousCustomerUserToken('', [], $customerVisitor, $organization);
        $this->client->getContainer()->get('security.token_storage')->setToken($token);

        $action = $this->client->getContainer()->get('oro_action.action.run_action_group');
        $action->initialize([
            'action_group' => 'oro_cookie_consent_set_accepted_cookies'
        ]);
        $action->execute([]);

        $this->assertTrue($customerVisitor->getCookiesAccepted());
    }
}
