@regression
@ticket-BB-21075
Feature: Guest Opens Cookie Consent Page
  In order to read detailed information about cookie consent policy
  As a guest buyer
  I want to have ability to open cookie consent policy page

  Scenario: Feature Background
    Given I disable configuration options:
      | oro_frontend.guest_access_enabled |
    And I enable configuration options:
      | oro_cookie_consent.show_banner    |

  Scenario: I go by the Cookie Policy link
    Given I am on homepage
    Then I should see an "Cookie Banner" element
    When I click "Cookie Policy"
    Then I should be on "/cookie-policy"

  Scenario: I accept the cookie policy and should not see the banner again
    Given I am on homepage
    Then I should see "This website uses cookies to provide you with the best user experience"
    And I should see "Yes, Accept"
    And I click "Yes, Accept"
    Then I should not see "This website uses cookies to provide you with the best user experience"
    When I reload the page
    Then I should not see "This website uses cookies to provide you with the best user experience"
