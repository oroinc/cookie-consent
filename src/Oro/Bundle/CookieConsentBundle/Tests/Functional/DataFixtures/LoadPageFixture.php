<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadPageFixture extends AbstractFixture implements ContainerAwareInterface
{
    public const PAGE_WITH_DEFAULT_LOCALIZATION = 'page-with-default-localization';
    public const PAGE_WITH_BASE_LOCALIZATION = 'page-with-base-localization';

    public const TITLE_WITH_DEFAULT_LOCALIZATION = 'Title with default localization';
    public const URL_WITH_DEFAULT_LOCALIZATION = '/slug-url-with-default-localization';

    public const TITLE_WITH_BASE_LOCALIZATION = 'Title with base localization';
    public const URL_WITH_BASE_LOCALIZATION = '/slug-url-with-base-localization';

    use ContainerAwareTrait;

    public function load(ObjectManager $manager)
    {
        /* @var LocalizationManager $localizationManager */
        $localizationManager = $this->container->get('oro_locale.manager.localization');

        $title = new LocalizedFallbackValue();
        $title->setString('Title');

        $titleWithDefaultLocalization = new LocalizedFallbackValue();
        $titleWithDefaultLocalization->setString(self::TITLE_WITH_DEFAULT_LOCALIZATION);
        $titleWithDefaultLocalization->setLocalization($localizationManager->getDefaultLocalization());

        $entityWithDefaultLocalization = new Page();
        $entityWithDefaultLocalization->addTitle($title);
        $entityWithDefaultLocalization->addTitle($titleWithDefaultLocalization);

        $slug = new Slug();
        $slug->setRouteName('route_name');
        $slug->setRouteParameters([]);
        $slug->setUrl('/slug-url');

        $slugWithDefaultLocalization = new Slug();
        $slugWithDefaultLocalization->setRouteName('default_route_name');
        $slugWithDefaultLocalization->setUrl(self::URL_WITH_DEFAULT_LOCALIZATION);
        $slugWithDefaultLocalization->setRouteParameters([]);
        $slugWithDefaultLocalization->setLocalization($localizationManager->getDefaultLocalization());

        $entityWithDefaultLocalization->addSlug($slug);
        $entityWithDefaultLocalization->addSlug($slugWithDefaultLocalization);

        $manager->persist($entityWithDefaultLocalization);
        $this->setReference(self::PAGE_WITH_DEFAULT_LOCALIZATION, $entityWithDefaultLocalization);

        $baseTitle = new LocalizedFallbackValue();
        $baseTitle->setString(self::TITLE_WITH_BASE_LOCALIZATION);

        $entityWithBaseLocalization = new Page();
        $entityWithBaseLocalization->addTitle($baseTitle);

        $slugWithBaseLocalization = new Slug();
        $slugWithBaseLocalization->setRouteName('default_route_name');
        $slugWithBaseLocalization->setUrl(self::URL_WITH_BASE_LOCALIZATION);
        $slugWithBaseLocalization->setRouteParameters([]);

        $entityWithBaseLocalization->addSlug($slugWithBaseLocalization);

        $manager->persist($entityWithBaseLocalization);
        $this->setReference(self::PAGE_WITH_BASE_LOCALIZATION, $entityWithBaseLocalization);

        $manager->flush();
    }
}
