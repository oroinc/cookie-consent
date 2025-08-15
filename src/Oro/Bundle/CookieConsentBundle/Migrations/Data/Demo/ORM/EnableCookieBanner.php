<?php

namespace Oro\Bundle\CookieConsentBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Enables cookie banner on storefront.
 */
class EnableCookieBanner extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $configManager = $this->container->get('oro_config.global');
        $configManager->set('oro_cookie_consent.show_banner', true);
        $configManager->flush();
    }
}
