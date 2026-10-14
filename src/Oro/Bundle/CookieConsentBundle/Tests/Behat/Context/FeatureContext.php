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
     * The banner hides at once and saves the consent with an async request.
     * The afterStep hook does not wait for AJAX on the login page, so this step waits for the request.
     * The button is found by its action, not by its label, so the step works in every language.
     *
     * @When /^(?:|I )accept the cookie policy$/
     */
    public function acceptCookiePolicy(): void
    {
        $button = $this->getSession()->getPage()->find('css', '.cookie-banner-view__actions [data-action="accept"]');
        self::assertNotNull($button, 'The "accept" button of the cookie banner is not found');
        $button->click();
        $this->waitForAjax();
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
            '//a[contains(translate(text(),"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"%s")]' .
            '//ancestor::div[contains(concat(" ", normalize-space(@class), " "), " consent-item ")]' .
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
