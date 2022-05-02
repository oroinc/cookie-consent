<?php

namespace Oro\Bundle\CookieConsentBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CookieConsentBundle\DependencyInjection\Configuration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Enables cookie banner on storefront.
 */
class EnableCookieBanner extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $configManager = $this->container->get('oro_config.global');
        $configManager->set(Configuration::ROOT_NODE . '.show_banner', true);

        $configManager->flush();
    }
}
