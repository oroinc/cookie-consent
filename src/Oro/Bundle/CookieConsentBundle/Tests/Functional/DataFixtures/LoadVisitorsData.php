<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;

class LoadVisitorsData extends AbstractFixture
{
    public const CUSTOMER_VISITOR = 'customer_visitor';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $customerVisitor = new CustomerVisitor();
        $customerVisitor->setCookiesAccepted(false);
        $customerVisitor->setSessionId('sessionId');

        $manager->persist($customerVisitor);
        $this->setReference(self::CUSTOMER_VISITOR, $customerVisitor);
    }
}
