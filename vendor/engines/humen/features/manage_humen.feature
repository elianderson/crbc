@humen
Feature: Humen
  In order to have humen on my website
  As an administrator
  I want to manage humen

  Background:
    Given I am a logged in refinery user
    And I have no humen

  @humen-list @list
  Scenario: Humen List
   Given I have humen titled UniqueTitleOne, UniqueTitleTwo
   When I go to the list of humen
   Then I should see "UniqueTitleOne"
   And I should see "UniqueTitleTwo"

  @humen-valid @valid
  Scenario: Create Valid Human
    When I go to the list of humen
    And I follow "Add New Human"
    And I fill in "Fname" with "This is a test of the first string field"
    And I press "Save"
    Then I should see "'This is a test of the first string field' was successfully added."
    And I should have 1 human

  @humen-invalid @invalid
  Scenario: Create Invalid Human (without fname)
    When I go to the list of humen
    And I follow "Add New Human"
    And I press "Save"
    Then I should see "Fname can't be blank"
    And I should have 0 humen

  @humen-edit @edit
  Scenario: Edit Existing Human
    Given I have humen titled "A fname"
    When I go to the list of humen
    And I follow "Edit this human" within ".actions"
    Then I fill in "Fname" with "A different fname"
    And I press "Save"
    Then I should see "'A different fname' was successfully updated."
    And I should be on the list of humen
    And I should not see "A fname"

  @humen-duplicate @duplicate
  Scenario: Create Duplicate Human
    Given I only have humen titled UniqueTitleOne, UniqueTitleTwo
    When I go to the list of humen
    And I follow "Add New Human"
    And I fill in "Fname" with "UniqueTitleTwo"
    And I press "Save"
    Then I should see "There were problems"
    And I should have 2 humen

  @humen-delete @delete
  Scenario: Delete Human
    Given I only have humen titled UniqueTitleOne
    When I go to the list of humen
    And I follow "Remove this human forever"
    Then I should see "'UniqueTitleOne' was successfully removed."
    And I should have 0 humen
 