<?php
// This file is part of the block_mylearning plugin for Moodle - http://moodle.org/
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

namespace block_mylearning\external;

use core\external\exporter;
use core_course\external\course_summary_exporter;
use core_course_category;
use renderer_base;
use stdClass;
use tool_program\constants;

/**
 * Class for exporting course data to the mylearning overview view.
 *
 * @package    block_mylearning
 * @copyright  2020 Moodle Pty Ltd <support@moodle.com>
 * @author     2020 Mikel Martín <mikel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class course_exporter extends exporter {
    /**
     * Returns a list of objects that are related.
     *
     * @return array
     */
    protected static function define_related(): array {
        return [
            'context' => 'context',
            'uniqueid' => 'string',
            'course' => 'stdClass',
            'progress' => 'float',
            'completionenabled' => 'bool',
            'iscompleted' => 'bool',
            'lastcourseaccess' => 'stdClass[]?',
            'startdate' => 'int',
        ];
    }

    /**
     * Return the list of additional, generated dynamically from the given properties.
     *
     * @return array
     */
    protected static function define_other_properties(): array {
        return [
            'id' => [
                'type' => PARAM_INT,
            ],
            'uniqueid' => [
                'type' => PARAM_TEXT,
            ],
            'fullname' => [
                'type' => PARAM_TEXT,
            ],
            'categoryname' => [
                'type' => PARAM_TEXT,
            ],
            'image' => [
                'type' => PARAM_RAW,
            ],
            'progress' => [
                'type' => PARAM_INT,
            ],
            'completionenabled' => [
                'type' => PARAM_BOOL,
            ],
            'iscompleted' => [
                'type' => PARAM_BOOL,
            ],
            'startdatestr' => [
                'type' => PARAM_TEXT,
            ],
            'enddatestr' => [
                'type' => PARAM_TEXT,
            ],
            'isprogram' => [
                'type' => PARAM_BOOL,
            ],
            'lastaccess' => [
                'type' => PARAM_INT
            ],
            'lowestduedate' => [
                'type' => PARAM_INT,
            ],
        ];
    }

    /**
     * Other values
     *
     * @param renderer_base $output
     * @return array
     */
    protected function get_other_values(renderer_base $output): array {
        global $OUTPUT;

        /** @var stdClass $course */
        $course = $this->related['course'];
        /** @var int $iscompleted */
        $iscompleted = $this->related['iscompleted'];
        /** @var int $completionenabled */
        $completionenabled = $this->related['completionenabled'];
        /** @var int $progress */
        $progress = $this->related['progress'];
        /** @var int $userstartdate */
        $userstartdate = $this->related['startdate'];

        $exporteddata = new stdClass();
        $exporteddata->uniqueid = $this->related['uniqueid'];
        $exporteddata->id = $course->id;
        $exporteddata->fullname = format_string($course->fullname);
        $category = core_course_category::get($course->category, IGNORE_MISSING);
        $exporteddata->categoryname = isset($category) ? $category->get_formatted_name() : '';
        $exporteddata->image = course_summary_exporter::get_course_image($course);
        if (!$exporteddata->image) {
            $exporteddata->image = $OUTPUT->get_generated_image_for_id($course->id);
        }

        $exporteddata->iscompleted = $iscompleted;
        $exporteddata->completionenabled = $completionenabled;
        $exporteddata->progress = (int)round($progress);
        $exporteddata->isprogram = false;

        $exporteddata->lastaccess = $this->related['lastcourseaccess'][$course->id]->timeaccess ?? 0;

        // Get user enrolment start date if is after course start date.
        $userstartdate = ($userstartdate > 0) ? $userstartdate : $course->startdate ?? 0;

        if ($userstartdate != 0) {
            $startdate = userdate($userstartdate, get_string('strftimedatefullshort', 'langconfig'));
        } else {
            $startdate = get_string('datetypenone', 'block_mylearning');
        }
        if ($course->enddate != 0) {
            $enddate = userdate($course->enddate, get_string('strftimedatefullshort', 'langconfig'));
            $exporteddata->lowestduedate = $course->enddate;
        } else {
            $enddate = get_string('datetypenone', 'block_mylearning');
            $exporteddata->lowestduedate = constants::MAX_DATE;
        }
        $exporteddata->startdatestr = get_string('coursestartdate', 'block_mylearning', $startdate);
        $exporteddata->enddatestr = get_string('courseenddate', 'block_mylearning', $enddate);

        return (array) $exporteddata;
    }
}
