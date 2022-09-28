<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class CookieConsentControllerTest extends WebTestCase
{
    public function testSetCookieAcceptedWithCustomerUserAsUser()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(
                LoadCustomerUserData::AUTH_USER,
                LoadCustomerUserData::AUTH_PW
            )
        );

        $registry = $this->getContainer()->get('doctrine');
        $user = $registry
            ->getRepository(CustomerUser::class)
            ->findOneBy(['username' => LoadCustomerUserData::AUTH_USER]);

        $this->assertFalse($user->getCookiesAccepted());

        $this->client->request('POST', $this->getUrl('oro_cookie_consent_set_cookies_accepted'));
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $this->assertEquals(['success' => true], \json_decode($result->getContent(), true));

        $this->assertTrue($user->getCookiesAccepted());
    }

    public function testSetCookieAcceptedAndAnonymousToken()
    {
        $this->initClient();

        $registry = $this->getContainer()->get('doctrine');
        /** @var EntityRepository $repository */
        $repository = $registry->getRepository(CustomerVisitor::class);
        $repository->createQueryBuilder('cv')->delete()->getQuery()->execute();

        $this->client->request('POST', $this->getUrl('oro_cookie_consent_set_cookies_accepted'));
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $this->assertEquals(['success' => true], \json_decode($result->getContent(), true));

        $visitors = $repository->findAll();

        $this->assertCount(1, $visitors);

        $visitor = reset($visitors);
        $this->assertTrue($visitor->getCookiesAccepted());
    }
}
