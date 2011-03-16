@people
Feature: People
  In order to have people on my website
  As an administrator
  I want to manage people

  Background:
    Given I am a logged in refinery user
    And I have no people

  @people-list @list
  Scenario: People List
   Given I have people titled UniqueTitleOne, UniqueTitleTwo
   When I go to the list of people
   Then I should see "UniqueTitleOne"
   And I should see "UniqueTitleTwo"

  @people-valid @valid
  Scenario: Create Valid Person
    When I go to the list of people
    And I follow "Add New Person"
    And I fill in "First Name" with "This is a test of the first string field"
    And I press "Save"
    Then I should see "'This is a test of the first string field' was successfully added."
    And I should have 1 person

  @people-invalid @invalid
  Scenario: Create Invalid Person (without first_name)
    When I go to the list of people
    And I follow "Add New Person"
    And I press "Save"
    Then I should see "First Name can't be blank"
    And I should have 0 people

  @people-edit @edit
  Scenario: Edit Existing Person
    Given I have people titled "A first_name"
    When I go to the list of people
    And I follow "Edit this person" within ".actions"
    Then I fill in "First Name" with "A different first_name"
    And I press "Save"
    Then I should see "'A different first_name' was successfully updated."
    And I should be on the list of people
    And I should not see "A first_name"

  @people-duplicate @duplicate
  Scenario: Create Duplicate Person
    Given I only have people titled UniqueTitleOne, UniqueTitleTwo
    When I go to the list of people
    And I follow "Add New Person"
    And I fill in "First Name" with "UniqueTitleTwo"
    And I press "Save"
    Then I should see "There were problems"
    And I should have 2 people

  @people-delete @delete
  Scenario: Delete Person
    Given I only have people titled UniqueTitleOne
    When I go to the list of people
    And I follow "Remove this person forever"
    Then I should see "'UniqueTitleOne' was successfully removed."
    And I should have 0 people
 