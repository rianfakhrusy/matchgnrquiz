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
 * @package    qtype_matchgnrquiz
 * @copyright  2011 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Matching question type conversion handler.
 */
class moodle1_qtype_matchgnrquiz_handler extends moodle1_qtype_handler {

    /**
     * @return array
     */
    public function get_question_subpaths() {
        return array(
            'MATCHOPTIONS',
            'MATCHS/MATCH',
        );
    }

    /**
     * Appends the matchgnrquiz specific information to the question.
     */
    public function process_question(array $data, array $raw) {
        global $CFG;

        // Populate the list of matchgnrquizes first to get their ids.
        // Note that the field is re-populated on restore anyway but let us
        // do our best to produce valid backup files.
        $matchgnrquizids = array();
        if (isset($data['matchgnrquizs']['matchgnrquiz'])) {
            foreach ($data['matchgnrquizs']['matchgnrquiz'] as $matchgnrquiz) {
                $matchgnrquizids[] = $matchgnrquiz['id'];
            }
        }

        // Convert matchgnrquiz options.
        if (isset($data['matchgnrquizoptions'])) {
            $matchgnrquizoptions = $data['matchgnrquizoptions'][0];
        } else {
            $matchgnrquizoptions = array('shuffleanswers' => 1);
        }
        $matchgnrquizoptions['id'] = $this->converter->get_nextid();
        $matchgnrquizoptions['subquestions'] = implode(',', $matchgnrquizids);
        $this->write_xml('matchgnrquizoptions', $matchgnrquizoptions, array('/matchgnrquizoptions/id'));

        // Convert matchgnrquizes.
        $this->xmlwriter->begin_tag('matchgnrquizes');
        if (isset($data['matchgnrquizs']['matchgnrquiz'])) {
            foreach ($data['matchgnrquizs']['matchgnrquiz'] as $matchgnrquiz) {
                // Replay the upgrade step 2009072100.
                $matchgnrquiz['questiontextformat'] = 0;
                if ($CFG->texteditors !== 'textarea' and $data['oldquestiontextformat'] == FORMAT_MOODLE) {
                    $matchgnrquiz['questiontext'] = text_to_html($matchgnrquiz['questiontext'], false, false, true);
                    $matchgnrquiz['questiontextformat'] = FORMAT_HTML;
                } else {
                    $matchgnrquiz['questiontextformat'] = $data['oldquestiontextformat'];
                }

                $matchgnrquiz['questiontext'] = $this->migrate_files(
                        $matchgnrquiz['questiontext'], 'qtype_matchgnrquiz', 'subquestion', $matchgnrquiz['id']);
                $this->write_xml('matchgnrquiz', $matchgnrquiz, array('/matchgnrquiz/id'));
            }
        }
        $this->xmlwriter->end_tag('matchgnrquizes');
    }
}
