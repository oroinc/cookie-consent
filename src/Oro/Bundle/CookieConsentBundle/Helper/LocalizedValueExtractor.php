<?php

namespace Oro\Bundle\CookieConsentBundle\Helper;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Model\FallbackType;

/**
 * Helper to work with localized values.
 */
class LocalizedValueExtractor
{
    /**
     * @param array $values
     * @param Localization $localization
     * @return null|mixed
     */
    public function getLocalizedFallbackValue(array $values, Localization $localization = null)
    {
        $value = $this->getValue($values, $localization);
        if ($value instanceof FallbackType) {
            switch ($value->getType()) {
                case FallbackType::PARENT_LOCALIZATION:
                    $value = $this->getLocalizedFallbackValue($values, $localization->getParentLocalization());
                    break;
                case FallbackType::SYSTEM:
                    $value = $this->getLocalizedFallbackValue($values);
                    break;
            }
        }

        if (!$value && $localization !== null) {
            $value = $this->getLocalizedFallbackValue($values); // get default value
        }

        return $value;
    }

    /**
     * @param array $values
     * @param Localization|null $localization
     * @return mixed|FallbackType|null
     */
    private function getValue(array $values, Localization $localization = null)
    {
        $key = null;
        if ($localization) {
            $key = $localization->getId();
        }

        return array_key_exists($key, $values) ? $values[$key] : null;
    }
}
