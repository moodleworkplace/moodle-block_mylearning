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
