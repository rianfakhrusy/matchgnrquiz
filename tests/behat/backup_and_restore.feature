@qtype @qtype_matchgnrquiz
Feature: Test duplicating a quiz containing a Matching question
  As a teacher
  In order re-use my courses containing Matching questions
  I need to be able to backup and restore them

  Background:
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype | name         | template |
      | Test questions   | matchgnrquiz | matchgnrquizing-001 | foursubq |
    And the following "activities" exist:
      | activity   | name      | course | idnumber |
      | quiz       | Test quiz | C1     | quiz1    |
    And quiz "Test quiz" contains the following questions:
      | matchgnrquizing-001 | 1 |
    And I log in as "admin"
    And I am on site homepage
    And I follow "Course 1"

  @javascript
  Scenario: Backup and restore a course containing a Matching question
    When I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    And I restore "test_backup.mbz" backup into a new course using this options:
      | Schema | Course name | Course 2 |
    And I navigate to "Question bank" node in "Course administration"
    And I click on "Edit" "link" in the "matchgnrquizing-001" "table_row"
    Then the following fields matchgnrquiz these values:
      | Question name                      | matchgnrquizing-001          |
      | Question text                      | Classify the animals. |
      | General feedback                   | General feedback.     |
      | Default mark                       | 1                     |
      | Shuffle                            | 1                     |
      | Question 1                         | frog                  |
      | Question 2                         | cat                   |
      | Question 3                         | newt                  |
      | Question 4                         |                       |
      | id_subanswers_0                    | amphibian             |
      | id_subanswers_1                    | mammal                |
      | id_subanswers_2                    | amphibian             |
      | id_subanswers_3                    | insect                |
