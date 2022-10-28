<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements
    OroPageObjectAware
{
    use PageObjectDictionary;

    private ?OroMainContext $mainContext = null;

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->mainContext = $environment->getContext(OroMainContext::class);
    }

    /**
     * I confirm Agreements "Terms and Conditions" at registration step
     *
     * @Then /^(?:|I )confirm Agreements "(?P<content>[\w\s]*)" at registration step$/
     */
    public function checkConsentAgreements($content)
    {
        $this->checkConsent($content);
    }

    /**
     * I confirm Agreements "Terms and Conditions" at the checkout if they are not confirmed
     *
     * @Then /^(?:|I )confirm Agreements "(?P<content>[\w\s]*)" at the checkout if they are not confirmed$/
     */
    public function checkConsentCheckoutAgreements($content)
    {
        if (!$this->checkConsent($content)) {
            return;
        }
        $this->mainContext->pressButton('Continue');
        $this->waitForAjax();
    }

    public function checkConsent(string $content): bool
    {
        $xpath = sprintf(
            '//a[contains(translate(text(),"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"%s")]'.
            '//ancestor::div[contains(concat(" ", normalize-space(@class), " "), " consent-item ")]'.
            '//input[@type="checkbox"]',
            strtolower($content)
        );
        $label = $this->getPage()->find('xpath', $xpath);
        if (!$label || !$label->isValid()) {
            return false;
        }
        $label->click();
        $this->waitForAjax();

        $this->mainContext->scrollModalWindowToBottom();
        $this->mainContext->pressButtonInModalWindow('Agree');

        return true;
    }
}
