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
 * Defines the editing form for the matchgnrquiz question type.
 *
 * @package   qtype_matchgnrquiz
 * @copyright 2007 Jamie Pratt me@jamiep.org
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Match question type editing form definition.
 *
 * @copyright 2007 Jamie Pratt me@jamiep.org
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_matchgnrquiz_edit_form extends question_edit_form {

    protected function get_per_answer_fields($mform, $label, $gradeoptions,
            &$repeatedoptions, &$answersoption) {
        $mform->addElement('static', 'answersinstruct',
                get_string('availablechoices', 'qtype_matchgnrquiz'),
                get_string('filloutthreeqsandtwoas', 'qtype_matchgnrquiz'));

        $repeated = array();
        $repeated[] = $mform->createElement('editor', 'subquestions',
                $label, array('rows'=>3), $this->editoroptions);
        $repeated[] = $mform->createElement('text', 'subanswers',
                get_string('answer', 'question'), array('size' => 50, 'maxlength' => 255));
        $repeatedoptions['subquestions']['type'] = PARAM_RAW;
        $repeatedoptions['subanswers']['type'] = PARAM_TEXT;
        $answersoption = 'subquestions';
        return $repeated;
    }

    /**
     * Add question-type specific form fields.
     *
     * @param object $mform the form being built.
     */
    protected function definition_inner($mform) {
        $mform->addElement('duration', 'time', get_string('time', 'qtype_matchgnrquiz'),
                array('optional' => false));
        $mform->setType('time', PARAM_INT);
        $mform->addRule('time', null, 'required', null, 'client');
        $mform->addHelpButton('time', 'time', 'qtype_matchgnrquiz');

        $mform->addElement('text', 'difficulty', get_string('difficulty', 'qtype_matchgnrquiz'),
                array('size' => 7));
        $mform->setType('difficulty', PARAM_TEXT);
        $mform->setDefault('difficulty', 0.5);
        $mform->addRule('difficulty', null, 'required', null, 'client');
        $mform->addHelpButton('difficulty', 'difficulty', 'qtype_matchgnrquiz');

        $mform->addElement('text', 'distinguishingdegree', get_string('distinguishingdegree', 'qtype_matchgnrquiz'),
                array('size' => 7));
        $mform->setType('distinguishingdegree', PARAM_TEXT);
        $mform->setDefault('distinguishingdegree', 0.5);
        $mform->addRule('distinguishingdegree', null, 'required', null, 'client');
        $mform->addHelpButton('distinguishingdegree', 'distinguishingdegree', 'qtype_matchgnrquiz');

        $mform->addElement('advcheckbox', 'shuffleanswers',
                get_string('shuffle', 'qtype_matchgnrquiz'), null, null, array(0, 1));
        $mform->addHelpButton('shuffleanswers', 'shuffle', 'qtype_matchgnrquiz');
        $mform->setDefault('shuffleanswers', 1);

        $this->add_per_answer_fields($mform, get_string('questionno', 'question', '{no}'), 0);

        $this->add_combined_feedback_fields(true);
        $this->add_interactive_settings(true, true);
    }

    /**
     * Language string to use for 'Add {no} more {whatever we call answers}'.
     */
    protected function get_more_choices_string() {
        return get_string('blanksforxmorequestions', 'qtype_matchgnrquiz');
    }

    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);
        $question = $this->data_preprocessing_combined_feedback($question, true);
        $question = $this->data_preprocessing_hints($question, true, true);

        if (empty($question->options)) {
            return $question;
        }

        $question->shuffleanswers = $question->options->shuffleanswers;

        $question->time = $question->options->time;
        $question->difficulty = $question->options->difficulty;
        $question->distinguishingdegree = $question->options->distinguishingdegree;

        $key = 0;
        foreach ($question->options->subquestions as $subquestion) {
            $question->subanswers[$key] = $subquestion->answertext;

            $draftid = file_get_submitted_draft_itemid('subquestions[' . $key . ']');
            $question->subquestions[$key] = array();
            $question->subquestions[$key]['text'] = file_prepare_draft_area($draftid,
                    $this->context->id, 'qtype_matchgnrquiz', 'subquestion',
                    !empty($subquestion->id) ? (int) $subquestion->id : null,
                    $this->fileoptions, $subquestion->questiontext);
            $question->subquestions[$key]['format'] = $subquestion->questiontextformat;
            $question->subquestions[$key]['itemid'] = $draftid;
            $key++;
        }

        return $question;
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $answers = $data['subanswers'];
        $questions = $data['subquestions'];
        $questioncount = 0;
        $answercount = 0;

        if (($data['time']<=0)||(!is_numeric($data['time']))) {
            $errors['time'] = get_string('morethanzero', 'qtype_matchgnrquiz');
        }
        if (($data['difficulty']<0.0)||($data['difficulty']>1.0)||(!is_numeric($data['difficulty']))) {
            $errors['difficulty'] = get_string('betweenzeroandone', 'qtype_matchgnrquiz');
        }
        if (($data['distinguishingdegree']<0.0)||($data['distinguishingdegree']>1.0)||(!is_numeric($data['distinguishingdegree']))) {
            $errors['distinguishingdegree'] = get_string('betweenzeroandone', 'qtype_matchgnrquiz');
        }

        foreach ($questions as $key => $question) {
            $trimmedquestion = trim($question['text']);
            $trimmedanswer = trim($answers[$key]);
            if ($trimmedquestion != '') {
                $questioncount++;
            }
            if ($trimmedanswer != '' || $trimmedquestion != '') {
                $answercount++;
            }
            if ($trimmedquestion != '' && $trimmedanswer == '') {
                $errors['subanswers['.$key.']'] =
                        get_string('nomatchgnrquizinganswerforq', 'qtype_matchgnrquiz', $trimmedquestion);
            }
        }
        $numberqanda = new stdClass();
        $numberqanda->q = 2;
        $numberqanda->a = 3;
        if ($questioncount < 1) {
            $errors['subquestions[0]'] =
                    get_string('notenoughqsandas', 'qtype_matchgnrquiz', $numberqanda);
        }
        if ($questioncount < 2) {
            $errors['subquestions[1]'] =
                    get_string('notenoughqsandas', 'qtype_matchgnrquiz', $numberqanda);
        }
        if ($answercount < 3) {
            $errors['subanswers[2]'] =
                    get_string('notenoughqsandas', 'qtype_matchgnrquiz', $numberqanda);
        }
        return $errors;
    }

    public function qtype() {
        return 'matchgnrquiz';
    }
}
