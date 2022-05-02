<?php

namespace Oro\Bundle\CookieConsentBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Entity\Repository\PageRepository;
use Oro\Bundle\CookieConsentBundle\DependencyInjection\Configuration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Sets the default cookie policy page to the cookie banner config.
 */
class SetDefaultCookieConsentPage extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var PageRepository $pageRepository */
        $pageRepository = $this->container->get('oro_entity.doctrine_helper')->getEntityRepository(Page::class);
        $cookieConsentPage = $pageRepository->findOneByTitle(LoadCookieConsentPage::TITLE);
        if (!$cookieConsentPage) {
            throw new \LogicException('Can\'t find default cookie consent page!');
        }

        $configManager = $this->container->get('oro_config.global');
        $configManager->set(
            Configuration::ROOT_NODE . '.localized_landing_page_id',
            [null => $cookieConsentPage->getId()]
        );
        $configManager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCookieConsentPage::class
        ];
    }
}
