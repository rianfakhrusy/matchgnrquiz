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
 * Question type class for the matchgnrquizing question type.
 *
 * @package   qtype_matchgnrquiz
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');


/**
 * The matchgnrquizing question type class.
 *
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_matchgnrquiz extends question_type {

    public function get_question_options($question) {
        global $DB;
        parent::get_question_options($question);
        $question->options = $DB->get_record('qtype_matchgnrquiz_options',
                array('questionid' => $question->id));
        $question->options->subquestions = $DB->get_records('qtype_matchgnrquiz_subquest',
                array('questionid' => $question->id), 'id ASC');
        return true;
    }

    public function save_question_options($question) {
        global $DB;
        $context = $question->context;
        $result = new stdClass();

        $oldsubquestions = $DB->get_records('qtype_matchgnrquiz_subquest',
                array('questionid' => $question->id), 'id ASC');

        // Insert all the new question & answer pairs.
        foreach ($question->subquestions as $key => $questiontext) {
            if ($questiontext['text'] == '' && trim($question->subanswers[$key]) == '') {
                continue;
            }
            if ($questiontext['text'] != '' && trim($question->subanswers[$key]) == '') {
                $result->notice = get_string('nomatchgnrquizinganswer', 'qtype_matchgnrquiz', $questiontext);
            }

            // Update an existing subquestion if possible.
            $subquestion = array_shift($oldsubquestions);
            if (!$subquestion) {
                $subquestion = new stdClass();
                $subquestion->questionid = $question->id;
                $subquestion->questiontext = '';
                $subquestion->answertext = '';
                $subquestion->id = $DB->insert_record('qtype_matchgnrquiz_subquest', $subquestion);
            }

            $subquestion->questiontext = $this->import_or_save_files($questiontext,
                    $context, 'qtype_matchgnrquiz', 'subquestion', $subquestion->id);
            $subquestion->questiontextformat = $questiontext['format'];
            $subquestion->answertext = trim($question->subanswers[$key]);

            $DB->update_record('qtype_matchgnrquiz_subquest', $subquestion);
        }

        // Delete old subquestions records.
        $fs = get_file_storage();
        foreach ($oldsubquestions as $oldsub) {
            $fs->delete_area_files($context->id, 'qtype_matchgnrquiz', 'subquestion', $oldsub->id);
            $DB->delete_records('qtype_matchgnrquiz_subquest', array('id' => $oldsub->id));
        }

        // Save the question options.
        $options = $DB->get_record('qtype_matchgnrquiz_options', array('questionid' => $question->id));
        if (!$options) {
            $options = new stdClass();
            $options->questionid = $question->id;
            $options->correctfeedback = '';
            $options->partiallycorrectfeedback = '';
            $options->incorrectfeedback = '';
            $options->id = $DB->insert_record('qtype_matchgnrquiz_options', $options);
        }

        $options->time = $question->time;
        $options->difficulty = $question->difficulty;
        $options->distinguishingdegree = $question->distinguishingdegree;

        $options->shuffleanswers = $question->shuffleanswers;
        $options = $this->save_combined_feedback_helper($options, $question, $context, true);
        $DB->update_record('qtype_matchgnrquiz_options', $options);

        $this->save_hints($question, true);

        if (!empty($result->notice)) {
            return $result;
        }

        return true;
    }

    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);

        $question->shufflestems = $questiondata->options->shuffleanswers;

        $question->time = $questiondata->options->time;
        $question->difficulty = $questiondata->options->difficulty;
        $question->distinguishingdegree = $questiondata->options->distinguishingdegree;

        $this->initialise_combined_feedback($question, $questiondata, true);

        $question->stems = array();
        $question->choices = array();
        $question->right = array();

        foreach ($questiondata->options->subquestions as $matchgnrquizsub) {
            $ans = $matchgnrquizsub->answertext;
            $key = array_search($matchgnrquizsub->answertext, $question->choices);
            if ($key === false) {
                $key = $matchgnrquizsub->id;
                $question->choices[$key] = $matchgnrquizsub->answertext;
            }

            if ($matchgnrquizsub->questiontext !== '') {
                $question->stems[$matchgnrquizsub->id] = $matchgnrquizsub->questiontext;
                $question->stemformat[$matchgnrquizsub->id] = $matchgnrquizsub->questiontextformat;
                $question->right[$matchgnrquizsub->id] = $key;
            }
        }
    }

    protected function make_hint($hint) {
        return question_hint_with_parts::load_from_record($hint);
    }

    public function delete_question($questionid, $contextid) {
        global $DB;
        $DB->delete_records('qtype_matchgnrquiz_options', array('questionid' => $questionid));
        $DB->delete_records('qtype_matchgnrquiz_subquest', array('questionid' => $questionid));

        parent::delete_question($questionid, $contextid);
    }

    public function get_random_guess_score($questiondata) {
        $q = $this->make_question($questiondata);
        return 1 / count($q->choices);
    }

    public function get_possible_responses($questiondata) {
        $subqs = array();

        $q = $this->make_question($questiondata);

        foreach ($q->stems as $stemid => $stem) {

            $responses = array();
            foreach ($q->choices as $choiceid => $choice) {
                $responses[$choiceid] = new question_possible_response(
                        $q->html_to_text($stem, $q->stemformat[$stemid]) . ': ' . $choice,
                        ($choiceid == $q->right[$stemid]) / count($q->stems));
            }
            $responses[null] = question_possible_response::no_response();

            $subqs[$stemid] = $responses;
        }

        return $subqs;
    }

    public function move_files($questionid, $oldcontextid, $newcontextid) {
        global $DB;
        $fs = get_file_storage();

        parent::move_files($questionid, $oldcontextid, $newcontextid);

        $subquestionids = $DB->get_records_menu('qtype_matchgnrquiz_subquest',
                array('questionid' => $questionid), 'id', 'id,1');
        foreach ($subquestionids as $subquestionid => $notused) {
            $fs->move_area_files_to_new_context($oldcontextid,
                    $newcontextid, 'qtype_matchgnrquiz', 'subquestion', $subquestionid);
        }

        $this->move_files_in_combined_feedback($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_hints($questionid, $oldcontextid, $newcontextid);
    }

    protected function delete_files($questionid, $contextid) {
        global $DB;
        $fs = get_file_storage();

        parent::delete_files($questionid, $contextid);

        $subquestionids = $DB->get_records_menu('qtype_matchgnrquiz_subquest',
                array('questionid' => $questionid), 'id', 'id,1');
        foreach ($subquestionids as $subquestionid => $notused) {
            $fs->delete_area_files($contextid, 'qtype_matchgnrquiz', 'subquestion', $subquestionid);
        }

        $this->delete_files_in_combined_feedback($questionid, $contextid);
        $this->delete_files_in_hints($questionid, $contextid);
    }

    /**
     * If your question type has a table that extends the question table, and
     * you want the base class to automatically save, backup and restore the extra fields,
     * override this method to return an array where the first element is the table name,
     * and the subsequent entries are the column names (apart from id and questionid).
     *
     * @return mixed array as above, or null to tell the base class to do nothing.
     */
    public function extra_question_field() {
        return array('mdl_question_matchgnrquiz', //table name
            'time', 
            'difficulty',
            'distinguishingdegree'
        );
    }

    // IMPORT/EXPORT FUNCTIONS --------------------------------- .

    /*
     * Imports question from the Moodle XML format
     *
     * Imports question using information from extra_question_fields function
     * If some of you fields contains id's you'll need to reimplement this
     */
    
    public function export_to_xml($question, qformat_xml $format, $extra=null) {
        $fs = get_file_storage();
        $contextid = $question->contextid;
        // Get files used by the questiontext.
        $question->questiontextfiles = $fs->get_area_files(
                $contextid, 'question', 'questiontext', $question->id);
        // Get files used by the generalfeedback.
        $question->generalfeedbackfiles = $fs->get_area_files(
                $contextid, 'question', 'generalfeedback', $question->id);
        if (!empty($question->options->answers)) {
            foreach ($question->options->answers as $answer) {
                $answer->answerfiles = $fs->get_area_files(
                        $contextid, 'question', 'answer', $answer->id);
                $answer->feedbackfiles = $fs->get_area_files(
                        $contextid, 'question', 'answerfeedback', $answer->id);
            }
        }

        $extraquestionfields = $this->extra_question_field();

        if (!is_array($extraquestionfields)) {
            return false;
        }

        // Omit table name.
        array_shift($extraquestionfields);
        $expout='';
        foreach ($extraquestionfields as $field) {
            $exportedvalue = $format->xml_escape($question->options->$field);
            $expout .= "    <{$field}>{$exportedvalue}</{$field}>\n";
        }

        $expout .= "    <shuffleanswers>" .
                $format->get_single($question->options->shuffleanswers) .
                "</shuffleanswers>\n";
        $expout .= $format->write_combined_feedback($question->options, $question->id, $question->contextid);
        foreach ($question->options->subquestions as $subquestion) {
            $files = $fs->get_area_files($contextid, 'qtype_match',
                    'subquestion', $subquestion->id);
            $expout .= "    <subquestion " .
                    $format->format($subquestion->questiontextformat) . ">\n";
            $expout .= $format->writetext($subquestion->questiontext, 3);
            $expout .= $format->write_files($files);
            $expout .= "      <answer>\n";
            $expout .= $format->writetext($subquestion->answertext, 4);
            $expout .= "      </answer>\n";
            $expout .= "    </subquestion>\n";
        }

        return $expout;
    }

    /*
    * Imports question from the Moodle XML format
    *
    * Imports question using information from extra_question_fields function
    * If some of you fields contains id's you'll need to reimplement this
    */
    public function import_from_xml($data, $question, qformat_xml $format, $extra=null) {
        $question_type = $data['@']['type'];
        if ($question_type != $this->name()) {
            return false;
        }

        $extraquestionfields = $this->extra_question_field();
        if (!is_array($extraquestionfields)) {
            return false;
        }

        // Omit table name.
        array_shift($extraquestionfields);
        $qo = $format->import_headers($data);
        $qo->qtype = $question_type;

        foreach ($extraquestionfields as $field) {
            $qo->$field = $format->getpath($data, array('#', $field, 0, '#'), '');
        }

        $qo->shuffleanswers = $format->trans_single($format->getpath($data,
                array('#', 'shuffleanswers', 0, '#'), 1));

        // Run through subquestions.
        $qo->subquestions = array();
        $qo->subanswers = array();
        foreach ($data['#']['subquestion'] as $subqxml) {
            $qo->subquestions[] = $format->import_text_with_files($subqxml,
                    array(), '', $format->get_format($qo->questiontextformat));

            $answers = $format->getpath($subqxml, array('#', 'answer'), array());
            $qo->subanswers[] = $format->getpath($subqxml,
                    array('#', 'answer', 0, '#', 'text', 0, '#'), '', true);
        }

        $format->import_combined_feedback($qo, $data, true);
        $format->import_hints($qo, $data, true, false, $format->get_format($qo->questiontextformat));
        
        return $qo;
    }
}
