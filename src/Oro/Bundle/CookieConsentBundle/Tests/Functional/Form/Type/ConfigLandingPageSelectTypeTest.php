<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Functional\Form\Type;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Entity\Repository\PageRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CookieConsentBundle\DependencyInjection\Configuration;
use Oro\Bundle\CookieConsentBundle\Migrations\Data\Demo\ORM\EnableCookieBanner;
use Oro\Bundle\CookieConsentBundle\Migrations\Data\ORM\LoadCookieConsentPage;
use Oro\Bundle\CookieConsentBundle\Tests\Functional\DataFixtures\LandingPageDataFixture;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\PhpUtils\ArrayUtil;

class ConfigLandingPageSelectTypeTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private const CHANGED_SHOW_BANNER_VALUE = false;
    private const CHANGED_BANNER_TEXT_VALUE = 'Other text';

    private const WEBSITE_CHANGED_SHOW_BANNER_VALUE = true;
    private const WEBSITE_CHANGED_BANNER_TEXT_VALUE = 'Website text';

    /** @var ConfigManager */
    protected $configManager;

    /** @var ConfigManager */
    protected $websiteConfigManager;

    /** @var LocalizationManager */
    protected $localizationManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        if (!class_exists('Oro\Bundle\MultiWebsiteOrderBundle\MultiWebsiteOrderBundle')) {
            self::markTestSkipped('Can be tested only with MultiWebsiteBundle installed.');
        }
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LandingPageDataFixture::class, EnableCookieBanner::class]);
        $this->client->useHashNavigation(true);
        $this->configManager = self::getConfigManager('global');
        $this->websiteConfigManager = self::getConfigManager('website');
        $this->localizationManager = self::getContainer()->get('oro_locale.manager.localization');
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testChangeCookieConfigInSystemScope()
    {
        /** @var DoctrineHelper $doctrineHelper */
        $doctrineHelper = static::getContainer()->get('oro_entity.doctrine_helper');
        /** @var PageRepository $pageRepository */
        $pageRepository = $doctrineHelper->getEntityRepository(Page::class);

        $policyPage = $pageRepository->findOneByTitle(LoadCookieConsentPage::TITLE);
        $this->assertNotNull($policyPage);

        $this->assertTrue($this->configManager->get('oro_cookie_consent.show_banner'));
        $this->assertEquals(
            [null => Configuration::DEFAULT_BANNER_TEXT],
            $this->configManager->get('oro_cookie_consent.localized_banner_text')
        );
        $this->assertEquals(
            [null => $policyPage->getId()],
            $this->configManager->get('oro_cookie_consent.localized_landing_page_id')
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
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $localizationId = $this->localizationManager->getDefaultLocalization()->getId();

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

        $this->client->followRedirects(true);
        $this->client->request($form->getMethod(), $form->getUri(), $formData);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->configManager->reload();
        $this->assertEquals(
            self::CHANGED_SHOW_BANNER_VALUE,
            (bool)$this->configManager->get('oro_cookie_consent.show_banner')
        );
        $this->assertEquals(
            self::CHANGED_BANNER_TEXT_VALUE,
            $this->configManager->get('oro_cookie_consent.localized_banner_text')[null]
        );
        $this->assertEquals(
            $randomSelectedPage->getId(),
            $this->configManager->get('oro_cookie_consent.localized_landing_page_id')[null]
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testChangeCookieConfigInWebsiteScope()
    {
        $defaultWebsiteId = static::getContainer()->get('oro_website.manager')->getDefaultWebsite()->getId();
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_multiwebsite_config', [
                'id' => $defaultWebsiteId,
                'activeGroup' => 'commerce',
                'activeSubGroup' => 'customer_users'
            ])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var DoctrineHelper $doctrineHelper */
        $doctrineHelper = static::getContainer()->get('oro_entity.doctrine_helper');
        $pageRepository = $doctrineHelper->getEntityRepository(Page::class);

        /** @var Page[] $fixturePages */
        $fixturePages = $pageRepository->findAll();
        $randPageIndex = \random_int(0, \count($fixturePages) - 1);
        $randomSelectedPage = $fixturePages[$randPageIndex];

        $localizationId = $this->localizationManager->getDefaultLocalization()->getId();

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

        $this->client->followRedirects(true);
        $this->client->request($form->getMethod(), $form->getUri(), $formData);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->websiteConfigManager->reload($defaultWebsiteId);
        $this->assertEquals(
            self::WEBSITE_CHANGED_SHOW_BANNER_VALUE,
            (bool)$this->websiteConfigManager->get(
                'oro_cookie_consent.show_banner',
                false,
                false,
                $defaultWebsiteId
            )
        );
        $this->assertEquals(
            self::WEBSITE_CHANGED_BANNER_TEXT_VALUE,
            $this->websiteConfigManager->get(
                'oro_cookie_consent.localized_banner_text',
                false,
                false,
                $defaultWebsiteId
            )[null]
        );
        $this->assertEquals(
            $randomSelectedPage->getId(),
            $this->websiteConfigManager->get(
                'oro_cookie_consent.localized_landing_page_id',
                false,
                false,
                $defaultWebsiteId
            )[null]
        );
    }
}
