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

use context;
use core\external\exporter;
use renderer_base;
use stdClass;
use tool_program\persistent\program_course;
use tool_program\program_item;

/**
 * Class for exporting program course data.
 *
 * @property   program_course persistent
 * @package    block_mylearning
 * @copyright  2018 Moodle Pty Ltd <support@moodle.com>
 * @author     2018 Mitxel Moriana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class program_course_progress_exporter extends exporter {
    /**
     * Related objects definition.
     *
     * @return array
     */
    protected static function define_related(): array {
        return [
            'context' => 'context',
            'programitemcourse' => program_item::class,
        ];
    }

    /**
     * Other properties.
     *
     * @return array
     */
    protected static function define_other_properties(): array {
        return program_course_exporter::read_properties_definition() + [
                'level' => [
                    'type' => PARAM_INT,
                ],
                'iscompleted' => [
                    'type' => PARAM_BOOL,
                ],
                'totalitems' => [
                    'type' => PARAM_INT,
                ],
                'weight' => [
                    'type' => PARAM_INT,
                ],
                'completion' => [
                    'type' => PARAM_INT,
                ],
                'completionenabled' => [
                    'type' => PARAM_BOOL,
                ],
                'isenrolled' => [
                    'type' => PARAM_BOOL,
                ],
                'progress' => [
                    'type' => PARAM_INT,
                ],
                'unlocked' => [
                    'type' => PARAM_BOOL,
                ],
                'showprogress' => [
                    'type' => PARAM_BOOL,
                ],
                'statusmessage' => [
                    'type' => PARAM_TEXT,
                ],
                'statusmessagestr' => [
                    'type' => PARAM_TEXT,
                ],
                'ishidden' => [
                    'type' => PARAM_BOOL,
                ],
                'courseid' => [
                    'type' => PARAM_INT,
                ],
            ];
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param renderer_base $output The renderer.
     * @return array Keys are the property names, values are their values.
     */
    protected function get_other_values(renderer_base $output): array {
        /** @var context $context */
        $context = $this->related['context'];
        /** @var program_item $programitemcourse */
        $programitemcourse = $this->related['programitemcourse'];

        $course = $programitemcourse->get_course();
        $programid = $programitemcourse->get_programid();

        $exporter = new program_course_exporter($programitemcourse->get_persistent(), [
            'context' => $context,
            'course' => $course,
            'programid' => $programid,
        ]);
        $exporteddata = $exporter->export($output);

        $context = \context_course::instance($programitemcourse->get_courseid());
        $canviewhiddencourses = has_capability('moodle/course:viewhiddencourses', $context);
        $this->export_program_course_progress_details($exporteddata, $programitemcourse, $canviewhiddencourses);
        $this->export_program_course_status($exporteddata, $programitemcourse, $canviewhiddencourses);

        return (array) $exporteddata;
    }

    /**
     * Exports main progress details of the program course.
     *
     * @param stdClass $exporteddata
     * @param program_item $programitemcourse
     * @param bool $canviewhiddencourses
     */
    private function export_program_course_progress_details(stdClass $exporteddata, program_item $programitemcourse,
                                                            bool $canviewhiddencourses): void {
        $exporteddata->level = $programitemcourse->level;
        $exporteddata->iscompleted = $programitemcourse->iscompleted;
        $exporteddata->totalitems = $programitemcourse->totalitems;
        $exporteddata->weight = $programitemcourse->weight;
        $exporteddata->completion = $programitemcourse->completion;
        $exporteddata->completionenabled = $programitemcourse->completionenabled;
        $exporteddata->isenrolled = $programitemcourse->isenrolled;
        $exporteddata->progress = (int)$programitemcourse->progresspercentage;
        $exporteddata->unlocked = $programitemcourse->isunlocked;
        $exporteddata->ishidden = !$programitemcourse->get_course()->visible && !$canviewhiddencourses;
    }

    /**
     * Exports program course status information (locked, completed, available...).
     *
     * @param stdClass $exporteddata
     * @param program_item $programitemcourse
     * @param bool $canviewhiddencourses
     */
    private function export_program_course_status(stdClass $exporteddata, program_item $programitemcourse,
                                                  bool $canviewhiddencourses): void {
        if (!$programitemcourse->get_course()->visible && !$canviewhiddencourses) {
            $exporteddata->showprogress = false;
            $exporteddata->statusmessage = 'notavailable';
            $exporteddata->statusmessagestr = get_string('notavailable');
        } else if (!$programitemcourse->isunlocked) {
            if ($programitemcourse->iscompleted) {
                $exporteddata->showprogress = false;
                $exporteddata->statusmessage = 'pending';
                $exporteddata->statusmessagestr = get_string('pending', 'tool_program');
            } else {
                $exporteddata->showprogress = false;
                $exporteddata->statusmessage = 'locked';
                $exporteddata->statusmessagestr = get_string('locked', 'tool_program');
            }
        } else if ($programitemcourse->iscompleted) {
            $exporteddata->showprogress = false;
            $exporteddata->statusmessage = 'completed';
            $exporteddata->statusmessagestr = get_string('completed', 'tool_program');
        } else if (!$programitemcourse->isenrolled) {
            $exporteddata->showprogress = false;
            $exporteddata->statusmessage = 'available';
            $exporteddata->statusmessagestr = get_string('available', 'tool_program');
        } else if ($programitemcourse->completeditems > 0) {
            $exporteddata->showprogress = $programitemcourse->completionenabled;
            $exporteddata->statusmessage = 'inprogress';
            $exporteddata->statusmessagestr = get_string('inprogress', 'tool_program');
        } else {
            $exporteddata->showprogress = $programitemcourse->completionenabled;
            $exporteddata->statusmessage = 'enrolled';
            $exporteddata->statusmessagestr = get_string('enrolled', 'tool_program');
        }
    }
}
