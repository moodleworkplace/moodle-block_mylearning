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

use core\external\persistent_exporter;
use moodle_url;
use renderer_base;
use tool_program\persistent\program_course;
use tool_tenant\tenancy;

/**
 * Class for exporting program course data.
 *
 * @property   program_course persistent
 * @package    block_mylearning
 * @copyright  2018 Moodle Pty Ltd <support@moodle.com>
 * @author     2018 Mitxel Moriana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class program_course_exporter extends persistent_exporter {
    /**
     * Defines the persistent class.
     *
     * @return string
     */
    protected static function define_class(): string {
        return program_course::class;
    }

    /**
     * Related objects definition.
     *
     * @return array
     */
    protected static function define_related(): array {
        return [
            'context' => 'context',
            'programid' => 'int',
            'course' => 'stdClass'
        ];
    }

    /**
     * Other properties.
     *
     * @return array
     */
    protected static function define_other_properties(): array {
        return [
            'name' => [
                'type' => PARAM_TEXT
            ],
            'programid' => [
                'type' => PARAM_INT
            ],
            'parentsetid' => [
                'type' => PARAM_INT
            ],
            'url' => [
                'type' => PARAM_URL
            ],
            'items' => [
                'type' => PARAM_RAW,
                'multiple' => true,
                'optional' => true,
            ],
            'warning' => [
                'type' => PARAM_RAW
            ],
        ];
    }

    /**
     * Magic function to return parameters for format_string()
     *
     * @return array
     */
    protected function get_format_parameters_for_name() {
        return ['options' => ['escape' => false]];
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param renderer_base $output The renderer.
     * @return array Keys are the property names, values are their values.
     */
    protected function get_other_values(renderer_base $output): array {
        global $CFG;
        require_once($CFG->libdir.'/grouplib.php');

        $course = $this->related['course'];
        $programid = $this->related['programid'];

        $parent = $this->persistent->get('setid');
        $url = (string) new moodle_url('/course/view.php', ['id' => $course->id]);

        $warning = '';
        if (tenancy::is_shared_course($course)) {
            if ($course->groupmode == SEPARATEGROUPS) {
                $warning = ' ' . $output->pix_icon('i/caution', get_string('enrolinseparategroups', 'tool_tenant'));
            } else {
                $warning = ' ' . $output->pix_icon('req', get_string('enrolwithoutgroups', 'tool_tenant'));
            }
        }

        return [
            'name' => get_course_display_name_for_list($course),
            'programid' => $programid,
            'parentsetid' => $parent,
            'url' => $url,
            'items' => [],
            'warning' => $warning,
        ];
    }
}
