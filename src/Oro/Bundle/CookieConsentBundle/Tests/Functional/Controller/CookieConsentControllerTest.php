<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Functional\Controller;

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

        $this->client->request('POST', $this->getUrl('oro_cookie_consent_set_cookies_accepted'));
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $this->assertEquals(['success' => true], \json_decode($result->getContent(), true));

        $registry = $this->getContainer()->get('doctrine');
        $visitors = $registry
            ->getRepository(CustomerVisitor::class)
            ->findAll();

        $this->assertCount(1, $visitors);

        $visitor = reset($visitors);
        $this->assertTrue($visitor->getCookiesAccepted());
    }
}
