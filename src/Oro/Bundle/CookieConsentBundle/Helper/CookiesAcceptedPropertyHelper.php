<?php

namespace Oro\Bundle\CookieConsentBundle\Helper;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;

/**
 * Allow to set cookies accepted value to pre-defined property classes
 */
class CookiesAcceptedPropertyHelper
{
    /** @var PropertyAccess */
    private $propertyAccessor;

    /** @var array */
    private $fieldMapping = [
        CustomerUser::class => 'cookies_accepted',
        CustomerVisitor::class => 'cookies_accepted'
    ];

    public function __construct()
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * @param object $object
     *
     * @throws \LogicException
     *
     * @return bool
     */
    public function isCookiesAccepted($object) : bool
    {
        if (!is_object($object)) {
            return false;
        }

        foreach ($this->fieldMapping as $className => $fieldName) {
            if (is_a($object, $className)) {
                return (bool) $this->propertyAccessor->getValue($object, $fieldName);
            }
        }

        throw new \LogicException('Try to get value of cookies accepted from incorrect type of object.');
    }

    /**
     * @param object $object
     * @param bool $cookiesAcceptedValue
     *
     * @throws \LogicException
     *
     * @return void
     */
    public function setCookiesAccepted($object, bool $cookiesAcceptedValue)
    {
        if (!is_object($object)) {
            return;
        }

        foreach ($this->fieldMapping as $className => $fieldName) {
            if (is_a($object, $className)) {
                $this->propertyAccessor->setValue($object, $fieldName, $cookiesAcceptedValue);
                return;
            }
        }

        throw new \LogicException('Try to set value of cookies accepted to incorrect type of object.');
    }
}
