@map_labels
Feature: Map Labels
  In order to have map_labels on my website
  As an administrator
  I want to manage map_labels

  Background:
    Given I am a logged in refinery user
    And I have no map_labels

  @map_labels-list @list
  Scenario: Map Labels List
   Given I have map_labels titled UniqueTitleOne, UniqueTitleTwo
   When I go to the list of map_labels
   Then I should see "UniqueTitleOne"
   And I should see "UniqueTitleTwo"

  @map_labels-valid @valid
  Scenario: Create Valid Map Label
    When I go to the list of map_labels
    And I follow "Add New Map Label"
    And I fill in "Title" with "This is a test of the first string field"
    And I press "Save"
    Then I should see "'This is a test of the first string field' was successfully added."
    And I should have 1 map_label

  @map_labels-invalid @invalid
  Scenario: Create Invalid Map Label (without title)
    When I go to the list of map_labels
    And I follow "Add New Map Label"
    And I press "Save"
    Then I should see "Title can't be blank"
    And I should have 0 map_labels

  @map_labels-edit @edit
  Scenario: Edit Existing Map Label
    Given I have map_labels titled "A title"
    When I go to the list of map_labels
    And I follow "Edit this map_label" within ".actions"
    Then I fill in "Title" with "A different title"
    And I press "Save"
    Then I should see "'A different title' was successfully updated."
    And I should be on the list of map_labels
    And I should not see "A title"

  @map_labels-duplicate @duplicate
  Scenario: Create Duplicate Map Label
    Given I only have map_labels titled UniqueTitleOne, UniqueTitleTwo
    When I go to the list of map_labels
    And I follow "Add New Map Label"
    And I fill in "Title" with "UniqueTitleTwo"
    And I press "Save"
    Then I should see "There were problems"
    And I should have 2 map_labels

  @map_labels-delete @delete
  Scenario: Delete Map Label
    Given I only have map_labels titled UniqueTitleOne
    When I go to the list of map_labels
    And I follow "Remove this map label forever"
    Then I should see "'UniqueTitleOne' was successfully removed."
    And I should have 0 map_labels
 