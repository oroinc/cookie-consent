<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\Page;

/**
 * Fixture that creates 3 pages for FormType test
 */
class LandingPageDataFixture extends AbstractFixture
{
    public const FIXTURE_PAGE_1_CONTENT = '<div>Fixture Page 1</div>';
    public const FIXTURE_PAGE_2_CONTENT = '<div>Fixture Page 2</div>';
    public const FIXTURE_PAGE_3_CONTENT = '<div>Fixture Page 3</div>';

    public const PAGES_DATA = [
        [
            'content' => self::FIXTURE_PAGE_1_CONTENT
        ],
        [
            'content' => self::FIXTURE_PAGE_2_CONTENT
        ],
        [
            'content' => self::FIXTURE_PAGE_3_CONTENT
        ]
    ];

    /**
     * Load data fixtures with the passed EntityManager
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::PAGES_DATA as $pageData) {
            $page = new Page();
            $page->setContent($pageData['content']);
            $manager->persist($page);
        }

        $manager->flush();
    }
}
