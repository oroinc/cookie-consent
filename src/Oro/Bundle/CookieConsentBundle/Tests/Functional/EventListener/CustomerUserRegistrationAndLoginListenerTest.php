<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Functional\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CookieConsentBundle\DependencyInjection\Configuration;
use Oro\Bundle\CookieConsentBundle\DependencyInjection\OroCookieConsentExtension;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Firewall\AnonymousCustomerUserAuthenticationListener;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerVisitors;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class CustomerUserRegistrationAndLoginListenerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private const CUSTOMER_USER_EMAIL = 'test@ggmail.com';
    private const CUSTOMER_USER_PASSWORD = 'testTest12345';

    /** @var ConfigManager */
    protected $configManager;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->configManager = self::getConfigManager('global');
        $this->loadFixtures([LoadCustomerVisitors::class]);
    }

    /**
     * @param Crawler $crawler
     * @param string $email
     * @return Crawler
     */
    protected function submitRegisterForm(Crawler $crawler, $email)
    {
        $form = $crawler->selectButton('Create An Account')->form();
        $submittedData = [
            'oro_customer_frontend_customer_user_register' => [
                '_token' => $form->get('oro_customer_frontend_customer_user_register[_token]')->getValue(),
                'companyName' => 'Test Company',
                'firstName' => 'First Name',
                'lastName' => 'Last Name',
                'email' => $email,
                'plainPassword' => [
                    'first' => self::CUSTOMER_USER_PASSWORD,
                    'second' => self::CUSTOMER_USER_PASSWORD
                ]
            ]
        ];

        $this->client->followRedirects(false);

        return $this->client->submit($form, $submittedData);
    }

    /**
     * @param array $criteria
     * @return CustomerUser
     */
    protected function getCustomerUser(array $criteria)
    {
        return static::getContainer()
            ->get('doctrine')
            ->getManagerForClass(CustomerUser::class)
            ->getRepository(CustomerUser::class)
            ->findOneBy($criteria)
        ;
    }

    protected function customerVisitorAcceptsCookies(CustomerVisitor $customerVisitor): void
    {
        $manager = static::getContainer()
            ->get('doctrine')
            ->getManagerForClass(CustomerVisitor::class)
        ;

        $customerVisitor->setCookiesAccepted(true);
        $manager->persist($customerVisitor);
        $manager->flush();
    }

    public function testAcceptCookiesBeforeRegistration()
    {
        /** @var CustomerVisitor $visitor */
        $visitor = $this->getReference(LoadCustomerVisitors::CUSTOMER_VISITOR);
        $this->customerVisitorAcceptsCookies($visitor);

        $this->configManager->set(
            OroCookieConsentExtension::getConfigKeyByName(Configuration::PARAM_NAME_SHOW_BANNER),
            true
        );
        $this->configManager->set('oro_customer.confirmation_required', false);
        $this->configManager->flush();

        // Imitate fixture Visitor is User, who is performing registration
        $serializedCredentials = \base64_encode(\json_encode([$visitor->getId(), $visitor->getSessionId()]));
        $this->client
            ->getCookieJar()
            ->set(
                new Cookie(AnonymousCustomerUserAuthenticationListener::COOKIE_NAME, $serializedCredentials)
            )
        ;
        $crawler = $this->client->request('GET', $this->getUrl('oro_customer_frontend_customer_user_register'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->submitRegisterForm($crawler, self::CUSTOMER_USER_EMAIL);
        $crawler = $this->client->followRedirect();
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $user = $this->getCustomerUser(['email' => self::CUSTOMER_USER_EMAIL]);
        $this->assertNotEmpty($user);
        $this->assertTrue($user->isEnabled());
        $this->assertTrue($user->isConfirmed());
        $this->assertStringContainsString('Registration successful', $crawler->html());

        static::assertTrue($user->getCookiesAccepted());
    }

    /**
     * This method imitates security Firewall listener behavior on CustomerUser login
     */
    public function handleGetResponseEvent(GetResponseEvent $event): void
    {
        $container = static::getContainer();
        $request = $container->get('request_stack')->getCurrentRequest();
        /** @var Request $request */
        if (false !== \preg_match('@customer/user/login-check@ui', $request->getUri())) {
            $container->get('event_dispatcher')->dispatch(
                new InteractiveLoginEvent(
                    $request,
                    new UsernamePasswordToken(
                        $this->getFixtureLoadedCustomerUser(),
                        [],
                        'frontend',
                        []
                    )
                ),
                SecurityEvents::INTERACTIVE_LOGIN
            );

            $event->setResponse(new Response(200));
        }
    }

    private function getFixtureLoadedCustomerUser(): CustomerUser
    {
        $user = $this->getCustomerUser(['email' => LoadCustomerUserData::EMAIL]);
        $this->assertNotEmpty($user);

        return $user;
    }

    public function testAcceptCookiesBeforeLogin()
    {
        /** @var CustomerVisitor $visitor */
        $visitor = $this->getReference(LoadCustomerVisitors::CUSTOMER_VISITOR);
        $this->customerVisitorAcceptsCookies($visitor);

        $this->loadFixtures([LoadCustomerUserData::class]);

        $user = $this->getFixtureLoadedCustomerUser();
        $this->assertFalse($user->getCookiesAccepted());

        $this->configManager->set(
            OroCookieConsentExtension::getConfigKeyByName(Configuration::PARAM_NAME_SHOW_BANNER),
            true
        );
        $this->configManager->flush();

        // Imitate fixture Visitor is User, who is performing registration
        $serializedCredentials = \base64_encode(\json_encode([$visitor->getId(), $visitor->getSessionId()]));
        $this->client
            ->getCookieJar()
            ->set(
                new Cookie(AnonymousCustomerUserAuthenticationListener::COOKIE_NAME, $serializedCredentials)
            )
        ;
        $crawler = $this->client->request('GET', $this->getUrl('oro_customer_customer_user_security_login'));
        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);

        $form = $crawler->filter('form#form-login')->form();
        $formPhpValues = $form->getPhpValues();

        $formData = array_merge(
            $formPhpValues,
            [
                '_username' => LoadCustomerUserData::EMAIL,
                '_password' => LoadCustomerUserData::PASSWORD
            ]
        );

        self::getContainer()->get('event_dispatcher')->addListener(
            KernelEvents::REQUEST,
            [$this, 'handleGetResponseEvent']
        );

        $this->client->disableReboot();
        $this->client->followRedirects();
        $this->client->submit($form, $formData);

        $response = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($response, 200);

        $user = $this->getFixtureLoadedCustomerUser();
        $this->assertTrue($user->getCookiesAccepted());
    }
}
