<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Functional\Form\Type;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Entity\Repository\PageRepository;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CookieConsentBundle\DependencyInjection\Configuration;
use Oro\Bundle\CookieConsentBundle\Migrations\Data\ORM\LoadCookieConsentPage;
use Oro\Bundle\CookieConsentBundle\Tests\Functional\DataFixtures\LandingPageDataFixture;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * @dbIsolationPerTest
 */
class ConfigLandingPageSelectTypeTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private const CHANGED_SHOW_BANNER_VALUE = false;
    private const CHANGED_BANNER_TEXT_VALUE = 'Other text';

    private const WEBSITE_CHANGED_SHOW_BANNER_VALUE = true;
    private const WEBSITE_CHANGED_BANNER_TEXT_VALUE = 'Website text';

    #[\Override]
    protected function setUp(): void
    {
        if (!class_exists('Oro\Bundle\MultiWebsiteBundle\OroMultiWebsiteBundle')) {
            self::markTestSkipped('Can be tested only with MultiWebsiteBundle installed.');
        }

        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LandingPageDataFixture::class]);

        $configManager = self::getConfigManager();
        $configManager->set('oro_cookie_consent.show_banner', true);
        $configManager->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        /** @var PageRepository $pageRepository */
        $pageRepository = self::getContainer()->get('doctrine')->getRepository(Page::class);
        $policyPage = $pageRepository->findOneByTitle(LoadCookieConsentPage::TITLE);

        $configManager = self::getConfigManager();
        $configManager->set('oro_cookie_consent.show_banner', false);
        $configManager->set('oro_cookie_consent.localized_banner_text', [null => Configuration::DEFAULT_BANNER_TEXT]);
        $configManager->set('oro_cookie_consent.localized_landing_page_id', [null => $policyPage->getId()]);
        $configManager->flush();

        $websiteConfigManager = self::getConfigManager('website');
        $websiteConfigManager->set('oro_cookie_consent.show_banner', null);
        $websiteConfigManager->set('oro_cookie_consent.localized_banner_text', null);
        $websiteConfigManager->set('oro_cookie_consent.localized_landing_page_id', null);
        $websiteConfigManager->flush();
        $websiteConfigManager->reload();
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testChangeCookieConfigInSystemScope(): void
    {
        $configManager = self::getConfigManager();

        /** @var PageRepository $pageRepository */
        $pageRepository = self::getContainer()->get('doctrine')->getRepository(Page::class);
        $policyPage = $pageRepository->findOneByTitle(LoadCookieConsentPage::TITLE);
        self::assertNotNull($policyPage);

        self::assertTrue($configManager->get('oro_cookie_consent.show_banner'));
        self::assertEquals(
            [null => Configuration::DEFAULT_BANNER_TEXT],
            $configManager->get('oro_cookie_consent.localized_banner_text')
        );
        self::assertEquals(
            [null => $policyPage->getId()],
            $configManager->get('oro_cookie_consent.localized_landing_page_id')
        );

        /** @var Page[] $fixturePages */
        $fixturePages = $pageRepository->findAll();
        $randPageIndex = \random_int(0, \count($fixturePages) - 1);
        $randomSelectedPage = $fixturePages[$randPageIndex];

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_config_configuration_system',
                [
                    'activeGroup' => 'commerce',
                    'activeSubGroup' => 'customer_users'
                ]
            )
        );
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var LocalizationManager $localizationManager */
        $localizationManager = self::getContainer()->get('oro_locale.manager.localization');
        $localizationId = $localizationManager->getDefaultLocalization()->getId();

        $token = $this->getCsrfToken('customer_users')->getValue();
        $form = $crawler->selectButton('Save settings')->form();
        $formPhpValues = $form->getPhpValues();
        $formData = ArrayUtil::arrayMergeRecursiveDistinct(
            $formPhpValues,
            [
                'customer_users' => [
                    'oro_cookie_consent___show_banner' => [
                        'use_parent_scope_value' => false,
                        'value' => self::CHANGED_SHOW_BANNER_VALUE
                    ],
                    '_token' => $token
                ],
            ]
        );
        $formData['customer_users']['oro_cookie_consent___localized_banner_text'] = [
            'use_parent_scope_value' => false,
            'value' => [
                'default' => self::CHANGED_BANNER_TEXT_VALUE,
                'localizations' => [
                    $localizationId => [
                        'use_fallback' => true,
                        'fallback' => FallbackType::SYSTEM,
                    ]
                ]
            ],
        ];
        $formData['customer_users']['oro_cookie_consent___localized_landing_page_id'] = [
            'use_parent_scope_value' => false,
            'value' => [
                'default' => $randomSelectedPage->getId(),
                'localizations' => [
                    $localizationId => [
                        'use_fallback' => true,
                        'fallback' => FallbackType::SYSTEM,
                    ]
                ]
            ]
        ];

        $this->client->followRedirects();
        $this->client->request($form->getMethod(), $form->getUri(), $formData);

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);

        $configManager->reload();
        self::assertEquals(
            self::CHANGED_SHOW_BANNER_VALUE,
            (bool)$configManager->get('oro_cookie_consent.show_banner')
        );
        self::assertEquals(
            self::CHANGED_BANNER_TEXT_VALUE,
            $configManager->get('oro_cookie_consent.localized_banner_text')[null]
        );
        self::assertEquals(
            $randomSelectedPage->getId(),
            $configManager->get('oro_cookie_consent.localized_landing_page_id')[null]
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testChangeCookieConfigInWebsiteScope(): void
    {
        /** @var Website $website */
        $website = self::getContainer()->get('oro_website.manager')->getDefaultWebsite();
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_multiwebsite_config', [
                'id' => $website->getId(),
                'activeGroup' => 'commerce',
                'activeSubGroup' => 'customer_users'
            ])
        );

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Page[] $fixturePages */
        $fixturePages = self::getContainer()->get('doctrine')->getRepository(Page::class)->findAll();
        $randPageIndex = random_int(0, \count($fixturePages) - 1);
        $randomSelectedPage = $fixturePages[$randPageIndex];

        /** @var LocalizationManager $localizationManager */
        $localizationManager = self::getContainer()->get('oro_locale.manager.localization');
        $localizationId = $localizationManager->getDefaultLocalization()->getId();

        $token = $this->getCsrfToken('customer_users')->getValue();
        $form = $crawler->selectButton('Save settings')->form();
        $formPhpValues = $form->getPhpValues();
        $formData = ArrayUtil::arrayMergeRecursiveDistinct(
            $formPhpValues,
            [
                'customer_users' => [
                    'oro_cookie_consent___show_banner' => [
                        'use_parent_scope_value' => false,
                        'value' => self::WEBSITE_CHANGED_SHOW_BANNER_VALUE
                    ],
                    'oro_cookie_consent___localized_banner_text' => [
                        'use_parent_scope_value' => false,
                        'value' => [
                            'default' => self::WEBSITE_CHANGED_BANNER_TEXT_VALUE,
                            'localizations' => [
                                $localizationId => [
                                    'use_fallback' => true,
                                    'fallback' => FallbackType::SYSTEM,
                                ]
                            ]
                        ],
                    ],
                    'oro_cookie_consent___localized_landing_page_id' => [
                        'use_parent_scope_value' => false,
                        'value' => [
                            'default' => $randomSelectedPage->getId(),
                            'localizations' => [
                                $localizationId => [
                                    'use_fallback' => true,
                                    'fallback' => FallbackType::SYSTEM,
                                ]
                            ]
                        ]
                    ],
                    '_token' => $token
                ],
            ]
        );

        $this->client->followRedirects();
        $this->client->request($form->getMethod(), $form->getUri(), $formData);

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);

        $websiteConfigManager = self::getConfigManager('website');
        $websiteConfigManager->reload($website);
        self::assertEquals(
            self::WEBSITE_CHANGED_SHOW_BANNER_VALUE,
            (bool)$websiteConfigManager->get('oro_cookie_consent.show_banner', false, false, $website)
        );
        self::assertEquals(
            self::WEBSITE_CHANGED_BANNER_TEXT_VALUE,
            $websiteConfigManager->get('oro_cookie_consent.localized_banner_text', false, false, $website)[null]
        );
        self::assertEquals(
            $randomSelectedPage->getId(),
            $websiteConfigManager->get('oro_cookie_consent.localized_landing_page_id', false, false, $website)[null]
        );
    }
}
