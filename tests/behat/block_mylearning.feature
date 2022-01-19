@block @block_mylearning
Feature: The 'My learning' block allows users to view their learning information
  In order to enable the logged in user block
  As a user
  I can add the 'My learning' block to show my learning information

  Background:
    Given the following "users" exist:
      | username  | firstname | lastname | email                | idnumber |
      | user1     | User      | One      | user1@example.com    | U1       |
    And the following "tool_program > programs" exist:
      | fullname |
      | Program1 |
      | Program2 |
    And the following "tool_program > program_users" exist:
      | user     | program  |
      | user1    | Program1 |
      | user1    | Program2 |
    And the following "courses" exist:
      | fullname               | shortname |
      | Non-program Course 001 | NPC001    |
      | Non-program Course 002 | NPC002    |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | user1    | NPC001 | student |
      | user1    | NPC002 | student |

  Scenario: View the logged in user block by a logged in user
    Given I log in as "user1"
    When I follow "Dashboard"
    And I press "Customise this page"
    And I add the "My learning" block
    And I configure the "My learning" block
    And I set the following fields to these values:
      | Region | content |
    And I press "Save changes"
    And I should see "Program1" in the "My learning" "block"
    And I should see "Program2" in the "My learning" "block"
    And I should see "Non-program Course 001" in the "My learning" "block"
    And I should see "Non-program Course 002" in the "My learning" "block"

  @javascript
  Scenario: Enable 'Programs' course filter option and respect users preferences
    Given I log in as "admin"
    And I navigate to "Plugins > Blocks > My learning" in site administration
    And I set the field "Show" to "Programs"
    And I press "Save changes"
    And I log out
    Then I log in as "user1"
    And I follow "Dashboard"
    And I press "Customise this page"
    And I add the "My learning" block
    And I should see "Program1" in the "My learning" "block"
    And I should see "Program2" in the "My learning" "block"
    And I should not see "Non-program Course 001" in the "My learning" "block"
    And I should not see "Non-program Course 002" in the "My learning" "block"
    # Make sure that user preferences prevail over default settings.
    And I click on "#programs-status-filter-dropdown" "css_element" in the "My learning" "block"
    And I click on "Courses" "link" in the "My learning" "block"
    And I should not see "Program1" in the "My learning" "block"
    And I should not see "Program2" in the "My learning" "block"
    And I should see "Non-program Course 001" in the "My learning" "block"
    And I should see "Non-program Course 002" in the "My learning" "block"
    And I reload the page
    And I should not see "Program1" in the "My learning" "block"
    And I should not see "Program2" in the "My learning" "block"
    And I should see "Non-program Course 001" in the "My learning" "block"
    And I should see "Non-program Course 002" in the "My learning" "block"

  Scenario: Enable default filter after user preferences are set
    Given I log in as "user1"
    When I follow "Dashboard"
    And I press "Customise this page"
    And I add the "My learning" block
    # This will save 'all' status in user preferences.
    And I should see "Program1" in the "My learning" "block"
    And I should see "Program2" in the "My learning" "block"
    And I should see "Non-program Course 001" in the "My learning" "block"
    And I should see "Non-program Course 002" in the "My learning" "block"
    And I log out
    And I log in as "admin"
    And I navigate to "Plugins > Blocks > My learning" in site administration
    And I set the field "Show" to "Programs"
    And I press "Save changes"
    And I log out
    Then I log in as "user1"
    And I follow "Dashboard"
    And I should see "Program1" in the "My learning" "block"
    And I should see "Program2" in the "My learning" "block"
    And I should see "Non-program Course 001" in the "My learning" "block"
    And I should see "Non-program Course 002" in the "My learning" "block"
