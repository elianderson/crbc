@announcements
Feature: Announcements
  In order to have announcements on my website
  As an administrator
  I want to manage announcements

  Background:
    Given I am a logged in refinery user
    And I have no announcements

  @announcements-list @list
  Scenario: Announcements List
   Given I have announcements titled UniqueTitleOne, UniqueTitleTwo
   When I go to the list of announcements
   Then I should see "UniqueTitleOne"
   And I should see "UniqueTitleTwo"

  @announcements-valid @valid
  Scenario: Create Valid Announcement
    When I go to the list of announcements
    And I follow "Add New Announcement"
    And I fill in "Title" with "This is a test of the first string field"
    And I press "Save"
    Then I should see "'This is a test of the first string field' was successfully added."
    And I should have 1 announcement

  @announcements-invalid @invalid
  Scenario: Create Invalid Announcement (without title)
    When I go to the list of announcements
    And I follow "Add New Announcement"
    And I press "Save"
    Then I should see "Title can't be blank"
    And I should have 0 announcements

  @announcements-edit @edit
  Scenario: Edit Existing Announcement
    Given I have announcements titled "A title"
    When I go to the list of announcements
    And I follow "Edit this announcement" within ".actions"
    Then I fill in "Title" with "A different title"
    And I press "Save"
    Then I should see "'A different title' was successfully updated."
    And I should be on the list of announcements
    And I should not see "A title"

  @announcements-duplicate @duplicate
  Scenario: Create Duplicate Announcement
    Given I only have announcements titled UniqueTitleOne, UniqueTitleTwo
    When I go to the list of announcements
    And I follow "Add New Announcement"
    And I fill in "Title" with "UniqueTitleTwo"
    And I press "Save"
    Then I should see "There were problems"
    And I should have 2 announcements

  @announcements-delete @delete
  Scenario: Delete Announcement
    Given I only have announcements titled UniqueTitleOne
    When I go to the list of announcements
    And I follow "Remove this announcement forever"
    Then I should see "'UniqueTitleOne' was successfully removed."
    And I should have 0 announcements
 