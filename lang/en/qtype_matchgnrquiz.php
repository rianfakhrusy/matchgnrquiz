<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'qtype_matchgnrquiz', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   qtype_matchgnrquiz
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['answer'] = 'Answer {$a}';
$string['availablechoices'] = 'Available choices';
$string['blanksforxmorequestions'] = 'Blanks for {no} more questions';
$string['correctansweris'] = 'The correct answer is: {$a}';
$string['deletedchoice'] = '[Deleted choice]';
$string['deletedsubquestion'] = 'This part of the question was deleted after the attempt was started.';

$string['betweenzeroandone'] = 'You must enter a number between 0 and 1 here.';
$string['morethanzero'] = 'You must enter a number more than 0 here.';
$string['difficulty'] = 'Difficulty';
$string['distinguishingdegree'] = 'Distinguishing Degree';
$string['time'] = 'Time';
$string['difficulty_help'] = 'How hard it is to answer the question correctly. The value must be a real number between 0 and 1. 0 is very easy and 1 is very hard.';
$string['distinguishingdegree_help'] = 'How able the question to distinguishing student ability answer the question correctly. The value must be a real number between 0 and 1. 0 is not able (everyone is either correcly answer the question or everyone incorrectly answer the question) and 1 is very able';
$string['time_help'] = 'Time needed to finish the question.';
$string['questionattributes'] = 'Question Attributes';

$string['filloutthreeqsandtwoas'] = 'You must provide at least two questions and three answers. You can provide extra wrong answers by giving an answer with a blank question. Entries where both the question and the answer are blank will be ignored.';
$string['nomatchgnrquizinganswer'] = 'You must specify an answer matchgnrquizing the question \'{$a}\'.';
$string['nomatchgnrquizinganswerforq'] = 'You must specify an answer for this question.';
$string['notenoughqsandas'] = 'You must supply at least {$a->q} questions and {$a->a} answers.';
$string['notenoughquestions'] = 'You must supply at least {$a} question and answer pairs.';
$string['shuffle'] = 'Shuffle';
$string['shuffle_help'] = 'If enabled, the order of the statements (answers) is randomly shuffled for each attempt, provided that "Shuffle within questions" in the activity settings is also enabled.';
$string['pleaseananswerallparts'] = 'Please answer all parts of the question.';
$string['pluginname'] = 'Matching for generated quiz';
$string['pluginname_help'] = 'Matching questions require the respondent to correctly matchgnrquiz a list of names or statements (questions) to another list of names or statements (answers).';
$string['pluginname_link'] = 'question/type/matchgnrquiz';
$string['pluginnameadding'] = 'Adding a Matching question';
$string['pluginnameediting'] = 'Editing a Matching question';
$string['pluginnamesummary'] = 'The answer to each of a number of sub-question must be selected from a list of possibilities.';
