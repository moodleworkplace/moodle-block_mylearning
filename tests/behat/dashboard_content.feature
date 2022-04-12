@block @block_mylearning @moodleworkplace @javascript
Feature: View mylearning block in content region
  In order to view my courses and programs
  As a student
  I can view them in mylearning block

  Background:
    Given the following "users" exist:
      | username | firstname | email                |
      | student1 | Student   | student1@example.com |
    And the following config values are set as admin:
      | dashboardlearning | 0 | theme_workplace |
    And default dashboard does not have any blocks except for My learning in "content" region

  Scenario: Mylearning block when user is not allocated to any program
    When I log in as "student1"
    # Check with 'Completed'
    Then I click on "Show" "button" in the "region-main" "region"
    And I follow "Completed"
    And I should see "Nothing to display"
    Then I click on "Display" "button" in the "region-main" "region"
    And I follow "Collapsed"
    And I should see "Nothing to display"
    # Check with 'Not completed'
    Then I click on "Show" "button" in the "region-main" "region"
    And I follow "Not completed"
    And I should see "Nothing to display"
    Then I click on "Display" "button" in the "region-main" "region"
    And I follow "Collapsed"
    And I should see "Nothing to display"
    # Check with 'All'
    Then I click on "Show" "button" in the "region-main" "region"
    And I follow "All"
    And I should see "Nothing to display"
    Then I click on "Display" "button" in the "region-main" "region"
    And I follow "Collapsed"
    And I should see "Nothing to display"

  Scenario: Mylearning block when user is allocated to some programs and courses
    Given the following "tool_program > programs" exist:
      | fullname |
      | Program1 |
      | Program2 |
    And the following "tool_program > program_users" exist:
      | user     | program  |
      | student1 | Program1 |
      | student1 | Program2 |
    And the following "courses" exist:
      | fullname               | shortname |
      | Non-program Course 001 | NPC001    |
      | Non-program Course 002 | NPC002    |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | NPC001 | student |
      | student1 | NPC002 | student |
    When I log in as "student1"
    And I should see "Program1" in the "region-main" "region"
    And I should see "Program2" in the "region-main" "region"
    And I should see "Non-program Course 001" in the "region-main" "region"
    And I should see "Non-program Course 002" in the "region-main" "region"

  Scenario: User should see the filter for program and course status in the mylearning block
    Given the following "tool_program > programs" exist:
      | fullname                     | duedatetype           | duedaterelative | generatecourses |
      | ProgramCompleted             | none                  | 1 weeks         | 1               |
      | ProgramNotCompleted          | none                  | 1 weeks         | 1               |
      | ProgramWithDueDateOverdue    | after_user_allocation | 0 weeks         | 1               |
      | ProgramWithDueDateNotOverdue | after_user_allocation | 2 weeks         | 1               |
      | ProgramUpcomingDueDate       | after_user_allocation | 1 days          | 1               |
    And the following "tool_program > program_users" exist:
      | user     | program                      |
      | student1 | ProgramCompleted             |
      | student1 | ProgramNotCompleted          |
      | student1 | ProgramWithDueDateOverdue    |
      | student1 | ProgramWithDueDateNotOverdue |
      | student1 | ProgramUpcomingDueDate       |
    And the following "tool_program > program_completions" exist:
      | program          | user     |
      | ProgramCompleted | student1 |
    And the following "courses" exist:
      | fullname               | shortname | enablecompletion |
      | Non-program Course 001 | NPC001    | 1                |
      | Non-program Course 002 | NPC002    | 1                |
    And the following "activities" exist:
      | activity | name      | intro     | course | idnumber | completion | completionview |
      | page     | PageName1 | PageDesc1 | NPC001 | PAGE1    | 1          | 1              |
      | page     | PageName2 | PageDesc2 | NPC001 | PAGE2    | 1          | 1              |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | NPC001 | student |
      | student1 | NPC002 | student |
    # Set completion criteria for courses from admin.
    When I log in as "admin"
    And I am on "Non-program Course 001" course homepage
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
      | PageName1 | 1 |
      | PageName2 | 1 |
    And I press "Save changes"
    And I log out
    # Complete course as a student.
    And I log in as "student1"
    And I am on "Non-program Course 001" course homepage
    And I toggle the manual completion state of "PageName1"
    And I toggle the manual completion state of "PageName2"
    And I am on homepage
    And I should see "ProgramCompleted" in the "region-main" "region"
    And I should see "ProgramNotCompleted" in the "region-main" "region"
    And I should see "ProgramWithDueDateOverdue" in the "region-main" "region"
    And I should see "ProgramWithDueDateNotOverdue" in the "region-main" "region"
    And I should see "ProgramUpcomingDueDate" in the "region-main" "region"
    And I should see "Non-program Course 001" in the "region-main" "region"
    And I should see "Non-program Course 002" in the "region-main" "region"
    And I should see "All" in the "region-main" "region"
    And I should not see "Completed" in the ".mylearning-status-filter" "css_element"
    # Check 'Completed'
    And I click on "Show" "button" in the "region-main" "region"
    And I follow "Completed"
    And I should see "ProgramCompleted" in the "region-main" "region"
    And I should see "Non-program Course 001" in the "region-main" "region"
    And I should not see "Non-program Course 002" in the "region-main" "region"
    And I should not see "ProgramNotCompleted" in the "region-main" "region"
    And I should not see "ProgramWithDueDateOverdue" in the "region-main" "region"
    And I should not see "ProgramWithDueDateNotOverdue" in the "region-main" "region"
    And I should not see "ProgramUpcomingDueDate" in the "region-main" "region"
    # Check 'Not completed'
    And I click on "Show" "button" in the "region-main" "region"
    And I follow "Not completed"
    And I should not see "ProgramCompleted" in the "region-main" "region"
    And I should not see "Non-program Course 001" in the "region-main" "region"
    And I should see "Non-program Course 002" in the "region-main" "region"
    And I should see "ProgramNotCompleted" in the "region-main" "region"
    And I should see "ProgramWithDueDateOverdue" in the "region-main" "region"
    And I should see "ProgramWithDueDateNotOverdue" in the "region-main" "region"
    And I should see "ProgramUpcomingDueDate" in the "region-main" "region"
    # Check 'All'
    And I click on "Show" "button" in the "region-main" "region"
    And I follow "All"
    And I should see "ProgramCompleted" in the "region-main" "region"
    And I should see "ProgramNotCompleted" in the "region-main" "region"
    And I should see "ProgramWithDueDateOverdue" in the "region-main" "region"
    And I should see "ProgramWithDueDateNotOverdue" in the "region-main" "region"
    And I should see "ProgramUpcomingDueDate" in the "region-main" "region"
    And I should see "Non-program Course 001" in the "region-main" "region"
    And I should see "Non-program Course 002" in the "region-main" "region"
    # Check 'Courses'.
    And I click on "Show" "button" in the "region-main" "region"
    And I follow "Courses"
    And I should see "Non-program Course 001" in the "region-main" "region"
    And I should see "Non-program Course 002" in the "region-main" "region"
    And I should not see "ProgramCompleted" in the "region-main" "region"
    And I should not see "ProgramNotCompleted" in the "region-main" "region"
    And I should not see "ProgramWithDueDateOverdue" in the "region-main" "region"
    And I should not see "ProgramWithDueDateNotOverdue" in the "region-main" "region"
    And I should not see "ProgramUpcomingDueDate" in the "region-main" "region"
    # Check 'Programs'.
    And I click on "Show" "button" in the "region-main" "region"
    And I follow "Programs"
    And I should not see "Non-program Course 001" in the "region-main" "region"
    And I should not see "Non-program Course 002" in the "region-main" "region"
    And I should see "ProgramCompleted" in the "region-main" "region"
    And I should see "ProgramNotCompleted" in the "region-main" "region"
    And I should see "ProgramWithDueDateOverdue" in the "region-main" "region"
    And I should see "ProgramWithDueDateNotOverdue" in the "region-main" "region"
    And I should see "ProgramUpcomingDueDate" in the "region-main" "region"
    # Check 'Completed' and search at same time
    And I click on "Show" "button" in the "region-main" "region"
    And I follow "Completed"
    And I set the field "mylearning-search-input" to "NotCompleted"
    And I should not see "ProgramCompleted" in the "region-main" "region"
    And I log out

  Scenario: Filter for program status in the mylearning block should show Nothing to display if no results
    Given the following "tool_program > programs" exist:
      | fullname            | duedatetype | duedaterelative |
      | ProgramNotCompleted | none        | 1 weeks         |
    And the following "tool_program > program_users" exist:
      | user     | program             |
      | student1 | ProgramNotCompleted |
    And the following "courses" exist:
      | fullname               | shortname | enablecompletion |
      | Non-program Course 001 | NPC001    | 1                |
    And the following "activities" exist:
      | activity | name      | intro     | course | idnumber | completion | completionview |
      | page     | PageName1 | PageDesc1 | NPC001 | PAGE1    | 1          | 1              |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | NPC001 | student |
    # Set completion criteria for courses from admin.
    When I log in as "admin"
    And I am on "Non-program Course 001" course homepage
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
      | PageName1 | 1 |
    And I press "Save changes"
    And I log out
    # Login as student
    When I log in as "student1"
    And I should see "ProgramNotCompleted" in the "region-main" "region"
    # Check 'Completed'
    And I click on "Show" "button" in the "region-main" "region"
    And I follow "Completed"
    And I should not see "ProgramNotCompleted" in the "region-main" "region"
    And I should not see "Non-program Course 001" in the "region-main" "region"
    And I should see "Nothing to display" in the "region-main" "region"
    And I log out

  Scenario: User should see the filter for program search in mylearning block
    Given the following "tool_program > programs" exist:
      | fullname  | duedatetype           | duedaterelative | generatecourses |
      | ProgramA1 | none                  | 0 weeks         | 1               |
      | ProgramB2 | after_user_allocation | 2 weeks         | 1               |
      | ProgramC3 | after_user_allocation | 5 weeks         | 1               |
      | ProgramD4 | after_user_allocation | 3 weeks         | 1               |
    And the following "tool_program > program_users" exist:
      | user     | program   |
      | student1 | ProgramA1 |
      | student1 | ProgramB2 |
      | student1 | ProgramC3 |
      | student1 | ProgramD4 |
    And the following "tool_program > program_completions" exist:
      | program   | user     |
      | ProgramA1 | student1 |
      | ProgramC3 | student1 |
    And the following "courses" exist:
      | fullname             | shortname | enablecompletion |
      | Non-program CourseA1 | NPC001    | 1                |
      | Non-program CourseB2 | NPC002    | 1                |
      | Non-program CourseC3 | NPC003    | 1                |
    And the following "activities" exist:
      | activity | name        | intro       | course | idnumber | completion | completionview |
      | page     | A1PageName1 | A1PageDesc1 | NPC001 | A1PAGE1  | 1          | 1              |
      | page     | A1PageName2 | A1PageDesc2 | NPC001 | A1PAGE2  | 1          | 1              |
      | page     | C3PageName1 | C3PageDesc1 | NPC003 | C3PAGE2  | 1          | 1              |
      | page     | C3PageName2 | C3PageDesc2 | NPC003 | C3PAGE2  | 1          | 1              |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | NPC001 | student |
      | student1 | NPC002 | student |
      | student1 | NPC003 | student |
    # Set completion criteria for courses from admin.
    When I log in as "admin"
    And I am on "Non-program CourseA1" course homepage
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
      | A1PageName1 | 1 |
      | A1PageName2 | 1 |
    And I press "Save changes"
    And I am on "Non-program CourseC3" course homepage
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
      | C3PageName1 | 1 |
      | C3PageName2 | 1 |
    And I press "Save changes"
    And I log out
    # Complete courses as a student.
    And I log in as "student1"
    # Complete Course
    And I am on "Non-program CourseA1" course homepage
    And I toggle the manual completion state of "A1PageName1"
    And I toggle the manual completion state of "A1PageName2"
    # Complete Course
    And I am on "Non-program CourseC3" course homepage
    And I toggle the manual completion state of "C3PageName1"
    And I toggle the manual completion state of "C3PageName2"
    And I am on homepage
    # Program /course search with All filter
    And I set the field "mylearning-search-input" to "A1"
    Then I should see "ProgramA1" in the "region-main" "region"
    And I should see "Non-program CourseA1" in the "region-main" "region"
    And I should not see "Non-program CourseB2" in the "region-main" "region"
    And I should not see "Non-program CourseC3" in the "region-main" "region"
    And I should not see "ProgramB2" in the "region-main" "region"
    And I should not see "ProgramC3" in the "region-main" "region"
    And I set the field "mylearning-search-input" to "C3"
    Then I should see "ProgramC3" in the "region-main" "region"
    And I should see "Non-program CourseC3" in the "region-main" "region"
    And I should not see "Non-program CourseA1" in the "region-main" "region"
    And I should not see "Non-program CourseB2" in the "region-main" "region"
    And I should not see "ProgramA1" in the "region-main" "region"
    And I should not see "ProgramD4" in the "region-main" "region"
    And I set the field "mylearning-search-input" to ""
    # Display List (Collapsed) - with All filter
    Then I click on "Display" "button" in the "region-main" "region"
    And I follow "Collapsed"
    And I should see "ProgramB2" in the "region-main" "region"
    And I should see "ProgramC3" in the "region-main" "region"
    And I should see "ProgramD4" in the "region-main" "region"
    And I should see "ProgramA1" in the "region-main" "region"
    And I should not see "Complete all in order" in the "ProgramA1" "block_mylearning > Dashboard item"
    And I click on "Expand" "link" in the "ProgramA1" "block_mylearning > Dashboard item"
    And I should see "Complete all in order" in the "ProgramA1" "block_mylearning > Dashboard item"
    And I should see "Non-program CourseA1" in the "region-main" "region"
    And I should see "Non-program CourseC3" in the "region-main" "region"
    And I should see "Non-program CourseB2" in the "region-main" "region"
    And I click on "Collapse" "link" in the "ProgramA1" "block_mylearning > Dashboard item"
    # Program /course search with Completed filter
    And I click on "Show" "button" in the "region-main" "region"
    And I follow "Completed"
    Then I click on "Display" "button" in the "region-main" "region"
    Then I follow "Expanded"
    And I set the field "mylearning-search-input" to "A1"
    Then I should see "ProgramA1" in the "region-main" "region"
    And I should see "Non-program CourseA1" in the "region-main" "region"
    And I should not see "Non-program CourseB2" in the "region-main" "region"
    And I should not see "Non-program CourseC3" in the "region-main" "region"
    And I should not see "ProgramB2" in the "region-main" "region"
    And I should not see "ProgramC3" in the "region-main" "region"
    And I set the field "mylearning-search-input" to "C3"
    Then I should see "ProgramC3" in the "region-main" "region"
    And I should see "Non-program CourseC3" in the "region-main" "region"
    And I should not see "Non-program CourseA1" in the "region-main" "region"
    And I should not see "Non-program CourseB2" in the "region-main" "region"
    And I should not see "ProgramA1" in the "region-main" "region"
    And I should not see "ProgramD4" in the "region-main" "region"
    And I set the field "mylearning-search-input" to ""
    And I should see "ProgramA1" in the "region-main" "region"
    And I should see "ProgramC3" in the "region-main" "region"
    And I should see "Non-program CourseA1" in the "region-main" "region"
    And I should see "Non-program CourseC3" in the "region-main" "region"
    And I should not see "ProgramB2" in the "region-main" "region"
    And I should not see "ProgramD4" in the "region-main" "region"
    And I should not see "Non-program CourseB2" in the "region-main" "region"
    # Display List (Collapsed) - Completed filter
    Then I click on "Display" "button" in the "region-main" "region"
    And I follow "Collapsed"
    And I should see "ProgramA1" in the "region-main" "region"
    And I should see "ProgramC3" in the "region-main" "region"
    And I should not see "ProgramB2" in the "region-main" "region"
    And I should not see "ProgramD4" in the "region-main" "region"
    And I should see "Non-program CourseA1" in the "region-main" "region"
    And I should see "Non-program CourseC3" in the "region-main" "region"
    And I should not see "Non-program CourseB2" in the "region-main" "region"
    And I should not see "Complete all in order" in the "ProgramA1" "block_mylearning > Dashboard item"
    And I click on "Expand" "link" in the "ProgramA1" "block_mylearning > Dashboard item"
    And I should see "Complete all in order" in the "ProgramA1" "block_mylearning > Dashboard item"
    And I click on "Collapse" "link" in the "ProgramA1" "block_mylearning > Dashboard item"
    # Program /course search with Not completed filter
    And I click on "Show" "button" in the "region-main" "region"
    And I follow "Not completed"
    Then I click on "Display" "button" in the "region-main" "region"
    Then I follow "Expanded"
    And I set the field "mylearning-search-input" to "B2"
    Then I should see "ProgramB2" in the "region-main" "region"
    And I should not see "Non-program CourseA1" in the "region-main" "region"
    And I should see "Non-program CourseB2" in the "region-main" "region"
    And I should not see "Non-program CourseC3" in the "region-main" "region"
    And I should see "ProgramB2" in the "region-main" "region"
    And I should not see "ProgramC3" in the "region-main" "region"
    And I set the field "mylearning-search-input" to "C3"
    Then I should not see "ProgramC3" in the "region-main" "region"
    And I should not see "Non-program CourseC3" in the "region-main" "region"
    And I should not see "Non-program CourseA1" in the "region-main" "region"
    And I should not see "Non-program CourseB2" in the "region-main" "region"
    And I should not see "ProgramA1" in the "region-main" "region"
    And I should not see "ProgramD4" in the "region-main" "region"
    And I set the field "mylearning-search-input" to ""
    And I should see "ProgramB2" in the "region-main" "region"
    And I should see "ProgramD4" in the "region-main" "region"
    And I should see "Non-program CourseB2" in the "region-main" "region"
    And I should not see "Non-program CourseA1" in the "region-main" "region"
    And I should not see "Non-program CourseC3" in the "region-main" "region"
    And I should not see "ProgramA1" in the "region-main" "region"
    And I should not see "ProgramC3" in the "region-main" "region"
    # Display List (Collapsed) - Not completed filter
    Then I click on "Display" "button" in the "region-main" "region"
    And I follow "Collapsed"
    And I should see "ProgramB2" in the "region-main" "region"
    And I should see "ProgramD4" in the "region-main" "region"
    And I should not see "ProgramA1" in the "region-main" "region"
    And I should not see "ProgramC3" in the "region-main" "region"
    And I should not see "Non-program CourseA1" in the "region-main" "region"
    And I should not see "Non-program CourseC3" in the "region-main" "region"
    And I should see "Non-program CourseB2" in the "region-main" "region"
    And I should not see "Complete all in order" in the "ProgramB2" "block_mylearning > Dashboard item"
    And I click on "Expand" "link" in the "ProgramB2" "block_mylearning > Dashboard item"
    And I should see "Complete all in order" in the "ProgramB2" "block_mylearning > Dashboard item"
    # Check also 'Name' sorting.
    And I click on "Sort" "button" in the "region-main" "region"
    And I follow "Name"
    And "Non-program CourseB2" "block_mylearning > Dashboard item" should appear before "ProgramB2" "block_mylearning > Dashboard item"
    And "ProgramB2" "block_mylearning > Dashboard item" should appear before "ProgramD4" "block_mylearning > Dashboard item"
    And I should not see "ProgramA1" in the "region-main" "region"
    And I should not see "ProgramC3" in the "region-main" "region"
    And I should not see "Non-program CourseA1" in the "region-main" "region"
    And I should not see "Non-program CourseC3" in the "region-main" "region"

  Scenario: User should be able to open and see the course and program information modal from mylearning block
    Given "1" tenants exist with "2" users and "0" courses in each
    And the following users allocations to tenants exist:
      | user     | tenant  |
      | student1 | Tenant1 |
    Given the following "tool_program > programs" exist:
      | fullname | archived | tenant  |
      | Program1 | 0        | Tenant1 |
    And the following "courses" exist:
      | fullname               | shortname | format | category | enablecompletion |
      | Course 1               | C1        | topics | CAT1     | 1                |
      | Non program Course 001 | NPC001    | topics | CAT1     | 1                |
      | Non program Course 002 | NPC002    | topics | CAT1     | 0                |
    And the following "tool_program > program_courses" exist:
      | program  | course |
      | Program1 | C1     |
    And the following "tool_program > program_users" exist:
      | user     | program  |
      | student1 | Program1 |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | NPC001 | student |
      | student1 | NPC002 | student |
    When I log in as "student1"
    And I change window size to "large"
    # See course info and progress
    And I should see "Non program Course 002" in the "region-main" "region"
    And "[role=progressbar]" "css_element" should not exist in the "Non program Course 002" "block_mylearning > Dashboard item"
    And I click on "Course information" "button" in the "Non program Course 002" "block_mylearning > Dashboard item"
    And "[role=progressbar]" "css_element" should not exist in the "Non program Course 002" "dialogue"
    And I click on "Close" "button" in the "Non program Course 002" "dialogue"
    And I should see "Non program Course 001" in the "region-main" "region"
    And I click on "Course information" "button" in the "Non program Course 001" "block_mylearning > Dashboard item"
    Then I should see "Non program Course 001" in the "Non program Course 001" "dialogue"
    And I should see "0% Completed" in the "Non program Course 001" "dialogue"
    And I click on "Go to course" "button" in the "Non program Course 001" "dialogue"
    # See program info and progress
    Then I am on homepage
    And I should see "Program1" in the "region-main" "region"
    And I click on "Program information" "button" in the "Program1" "block_mylearning > Dashboard item"
    Then I should see "Program1" in the "Program1" "dialogue"
    And I should see "0% Completed" in the "Program1" "dialogue"
    And I click on "Start" "button" in the "Program1" "dialogue"
    # See course info and progress in the program
    Then I am on homepage
    And I should see "Program1" in the "region-main" "region"
    And I should see "Course 1" in the "Program1" "block_mylearning > Dashboard item"
    And I click on "Course information" "button" in the "Program1" "block_mylearning > Dashboard item"
    And I should see "Course 1" in the "Course 1" "dialogue"
    And I should see "0% Completed" in the "Course 1" "dialogue"
    And I click on "Go to course" "button" in the "Course 1" "dialogue"

  Scenario: User is assigned to a same course via program and other enrollment method, user should be able see course under program and independent in mylearning block
    Given "1" tenants exist with "2" users and "0" courses in each
    And the following users allocations to tenants exist:
      | user     | tenant  |
      | student1 | Tenant1 |
    And the following "tool_program > programs" exist:
      | fullname | archived | tenant  |
      | Program1 | 0        | Tenant1 |
    And the following "courses" exist:
      | fullname | shortname | format | category | enablecompletion |
      | Course 1 | C1        | topics | CAT1     | 1                |
    And the following "tool_program > program_courses" exist:
      | program  | course |
      | Program1 | C1     |
    And the following "tool_program > program_users" exist:
      | user     | program  |
      | student1 | Program1 |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    When I log in as "student1"
    And I change window size to "large"
    # View course as an independent in expanded view
    And I should see "Course 1" in the "region-main" "region"
    And I click on "Course information" "button" in the "Course 1" "block_mylearning > Dashboard item"
    And I should see "Course 1" in the "Course 1" "dialogue"
    And I should see "0% Completed" in the "Course 1" "dialogue"
    And I click on "Close" "button" in the "Course 1" "dialogue"
    # View course under program tree in expanded view
    And I should see "Course 1" in the "Program1" "block_mylearning > Dashboard item"
    And I click on "Course information" "button" in the "Program1" "block_mylearning > Dashboard item"
    And I should see "Course 1" in the "Course 1" "dialogue"
    And I should see "0% Completed" in the "Course 1" "dialogue"
    And I click on "Close" "button" in the "Course 1" "dialogue"
    # View course as an independent in collpased view
    Then I click on "Display" "button" in the "region-main" "region"
    And I follow "Collapsed"
    And I should see "Course 1" in the "region-main" "region"
    And I click on "Course information" "button" in the ".dashboard-item .viewlist" "css_element"
    And I should see "Course 1" in the "Course 1" "dialogue"
    And I should see "0% Completed" in the "Course 1" "dialogue"
    And I click on "Close" "button" in the "Course 1" "dialogue"
    # View course under program tree in collpased view
    And I click on "Display" "button" in the "region-main" "region"
    And I follow "Collapsed"
    And I should not see "Complete all in order" in the "Program1" "block_mylearning > Dashboard item"
    And I click on "Expand" "link" in the "Program1" "block_mylearning > Dashboard item"
    And I should see "Complete all in order" in the "Program1" "block_mylearning > Dashboard item"
    And I should see "Course 1" in the "Program1" "block_mylearning > Dashboard item"
    And I click on "Course information" "button" in the ".collapse .programitem" "css_element"
    And I should see "Course 1" in the "Course 1" "dialogue"
    And I should see "0% Completed" in the "Course 1" "dialogue"
    And I click on "Close" "button" in the "Course 1" "dialogue"

  Scenario: Sort in the order of last access, name and due/end date in mylearning block
    Given the following "tool_program > programs" exist:
      | fullname  | duedatetype           | duedaterelative | generatecourses |
      | ProgramA1 | after_user_allocation | 3 days          | 1               |
      | ProgramB2 | after_user_allocation | 2 weeks         | 1               |
    And the following "tool_program > program_users" exist:
      | user     | program   |
      | student1 | ProgramA1 |
      | student1 | ProgramB2 |
    And the following "courses" exist:
      | fullname | shortname | enddate      |
      | CourseA1 | NPC001    | ##tomorrow## |
      | CourseB2 | NPC002    |              |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | NPC001 | student |
      | student1 | NPC002 | student |
    And I log in as "student1"
    And I change window size to "large"
    # Setup courses/progams last accesses.
    And I click on "Go to course" "button" in the "CourseA1" "block_mylearning > Dashboard item"
    And I am on homepage
    And I wait "1" seconds
    And I click on "Enrol" "button" in the "ProgramA1" "block_mylearning > Dashboard item"
    And I am on homepage
    And I wait "1" seconds
    And I click on "Go to course" "button" in the "CourseB2" "block_mylearning > Dashboard item"
    And I am on homepage
    # Check 'Last accessed' sorting (default).
    And I should see "Last accessed" in the "Sort" "button"
    Then "CourseB2" "block_mylearning > Dashboard item" should appear before "ProgramA1" "block_mylearning > Dashboard item"
    And "ProgramA1" "block_mylearning > Dashboard item" should appear before "CourseA1" "block_mylearning > Dashboard item"
    And "CourseA1" "block_mylearning > Dashboard item" should appear before "ProgramB2" "block_mylearning > Dashboard item"
    # Check 'Name' sorting.
    And I click on "Sort" "button" in the "region-main" "region"
    And I follow "Name"
    And "CourseA1" "block_mylearning > Dashboard item" should appear before "CourseB2" "block_mylearning > Dashboard item"
    And "CourseB2" "block_mylearning > Dashboard item" should appear before "ProgramA1" "block_mylearning > Dashboard item"
    And "ProgramA1" "block_mylearning > Dashboard item" should appear before "ProgramB2" "block_mylearning > Dashboard item"
    # Check 'Date' sorting.
    And I click on "Sort" "button" in the "region-main" "region"
    And I follow "Conclusion date"
    And "CourseA1" "block_mylearning > Dashboard item" should appear before "ProgramA1" "block_mylearning > Dashboard item"
    And "ProgramA1" "block_mylearning > Dashboard item" should appear before "ProgramB2" "block_mylearning > Dashboard item"
    And "ProgramB2" "block_mylearning > Dashboard item" should appear before "CourseB2" "block_mylearning > Dashboard item"
    # Check 'Last accessed' sorting.
    And I click on "Sort" "button" in the "region-main" "region"
    And I follow "Last accessed"
    Then "CourseB2" "block_mylearning > Dashboard item" should appear before "ProgramA1" "block_mylearning > Dashboard item"
    And "ProgramA1" "block_mylearning > Dashboard item" should appear before "CourseA1" "block_mylearning > Dashboard item"
    And "CourseA1" "block_mylearning > Dashboard item" should appear before "ProgramB2" "block_mylearning > Dashboard item"
    # Check 'Last accessed' sorting with 'Courses' filter.
    And I click on "Show" "button" in the "region-main" "region"
    And I follow "Courses"
    And "CourseB2" "block_mylearning > Dashboard item" should appear before "CourseA1" "block_mylearning > Dashboard item"
    And I should not see "ProgramA1" in the "region-main" "region"
    And I should not see "ProgramB2" in the "region-main" "region"
    And I click on "Show" "button" in the "region-main" "region"
    And I follow "All"
    # Check 'Last accessed' sorting with Search.
    And I set the field "mylearning-search-input" to "A1"
    And "ProgramA1" "block_mylearning > Dashboard item" should appear before "CourseA1" "block_mylearning > Dashboard item"
    And I should not see "CourseB2" in the "region-main" "region"
    And I should not see "ProgramB2" in the "region-main" "region"
    And I log out
