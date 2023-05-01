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

namespace block_mylearning\output;

use block_mylearning\api;
use block_mylearning\external\view_exporter;
use context_system;
use core_component;
use core_course\external\course_summary_exporter;
use core_course_category;
use core_course_renderer;
use core_user;
use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;
use tool_certification\api as certification_api;
use tool_program\api as program_api;

/**
 * Class view
 *
 * @package    block_mylearning
 * @copyright  2018 Moodle Pty Ltd <support@moodle.com>
 * @author     2018 Mitxel Moriana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class view implements templatable, renderable {
    /**
     * @var int|string
     */
    protected $userid;

    /**
     * view constructor.
     */
    public function __construct() {
        global $USER;
        $this->userid = $USER->id;
    }

    /**
     * Implementation of exporter from templatable interface
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $isprogrampluginincluded = (bool) core_component::get_component_directory('tool_program');

        // Get plugin config.
        $config = get_config('block_mylearning') ?: new stdClass();
        $config->show = !empty($config->show) ? $config->show : 'all';
        $config->display = !empty($config->display) ? $config->display : 'viewcards';
        $config->sort = !empty($config->sort) ? $config->sort : 'lastaccess';

        // Get user preference values.
        $userpreferencesstatus = get_user_preferences('block_mylearning_status_filter', $config->show);

        // In case that current 'block_mylearning_status_filter' value stored is 'programs' and tool_program don`t exists or,
        // it has not a valid string, we need to set a valid value option (config 'show' value) as user preference.
        if ((!$isprogrampluginincluded && $userpreferencesstatus === 'programs') ||
                !array_key_exists($userpreferencesstatus, api::get_status_filter_options())) {
            $userpreferencesstatus = $config->show;
        }

        $userpreferencesview = get_user_preferences('block_mylearning_view_filter', $config->display);
        $userpreferencessort = get_user_preferences('block_mylearning_sort_filter', $config->sort);

        // Default empty arrays when program plugin is not installed.
        $certifications = [];
        $certallocations = [];
        $programs = [];
        $programstreeprogress = [];
        $programsallocations = [];
        $programsenrolledcourses = [];
        $coursesinfo = [];

        if ($isprogrampluginincluded) {
            $iscertpluginincluded = (bool) core_component::get_component_directory('tool_certification');
            if ($iscertpluginincluded) {
                $certifications = certification_api::get_certifications_by_userid($this->userid);
                $certallocations = certification_api::get_user_allocations($this->userid);
            }
            $programs = program_api::get_user_accessible_programs($this->userid);
            $programstreeprogress = api::get_programs_tree_progress($programs, $this->userid);
            $programsallocations = program_api::get_user_allocations($this->userid);

            // Add program courses info to coursesinfo.
            foreach ($programstreeprogress as $programtree) {
                $programsenrolledcourses[$programtree->get_program()->get('id')] = $programtree->get_user_enrolments();
                $programcourses = $programtree->get_courses();
                foreach ($programcourses as $programcourse) {
                    $coursesinfo[$programcourse['courseid']] = $this->get_course_info([
                        'course' => $programcourse['course'],
                        'completionenabled' => $programcourse['completionenabled'],
                        'progress' => $programcourse['progress'],
                        'isenrolled' => $programcourse['isenrolled']
                    ]);
                }
            }
        }

        // Get user enrolled courses excluding courses enrolled only with the enrol program plugin.
        $courses = api::get_user_accessible_courses();
        $coursescompletion = api::get_user_courses_completion($courses, $this->userid);
        $coursesprogress = api::get_user_courses_progress($this->userid, $courses, $coursescompletion);
        // Add non program courses info to coursesinfo.
        foreach ($courses as $course) {
            $coursesinfo[$course->id] = $this->get_course_info([
                'course' => $course,
                'completionenabled' => $coursescompletion[$course->id]->completionenabled,
                'progress' => $coursesprogress[$course->id],
                'isenrolled' => true,
            ]);
        }

        $allcourseids = array_keys($coursesinfo);
        $coursesinfo = array_values($coursesinfo);

        $relateddata = [
            'context' => context_system::instance(),
            'user' => core_user::get_user($this->userid, '*', MUST_EXIST),
            'programs' => $programs,
            'courses' => $courses,
            'coursesprogress' => $coursesprogress,
            'coursescompletion' => $coursescompletion,
            'lastcourseaccess' => api::get_last_course_access($this->userid, $allcourseids),
            'programsallocations' => $programsallocations,
            'programstreeprogress' => $programstreeprogress,
            'certifications' => $certifications,
            'certificationscompletion' => $this->get_certifications_completion($certifications),
            'certificationsallocations' => $certallocations,
            'certificationsallocationsstatus' => $this->get_certification_allocations_status($certallocations),
            'statusfilterstr' => get_string($userpreferencesstatus, 'block_mylearning'),
            'statusfilterval' => $userpreferencesstatus,
            'showfilters' => true,
            'programsenrolledcourses' => $programsenrolledcourses,
            'view' => $userpreferencesview,
            'sort' => $userpreferencessort,
            'coursesmodals' => $coursesinfo,
            'coursestartdates' => api::get_all_user_course_startdates($this->userid, $allcourseids),
            'isdashboard' => true,
            'isprogrampluginincluded' => $isprogrampluginincluded,
        ];
        $exporter = new view_exporter(null, $relateddata);
        return $exporter->export($output);
    }

    /**
     * Get certification allocation status.
     *
     * @param array $allocations
     * @return array
     */
    private function get_certification_allocations_status(array $allocations): array {
        $status = [];
        foreach ($allocations as $allocation) {
            $allocationid = $allocation->get('id');
            $certificationid = $allocation->get('certificationid');
            $statuses = certification_api::get_user_allocation_status($certificationid, $this->userid);
            $status[$allocationid] = $statuses[0]['status'] ?? -1;
        }
        return $status;
    }

    /**
     * Get certifications completion.
     *
     * @param array $certifications
     * @return array
     */
    private function get_certifications_completion(array $certifications): array {
        $completions = [];
        foreach ($certifications as $certification) {
            $certid = $certification->get('id');
            $completion = certification_api::get_last_completion_record($this->userid, $certid);
            if ($completion) {
                $completions[$certid] = $completion;
            }
        }
        return $completions;
    }

    /**
     * Returns course information for the course information modal
     *
     * @param array $courseinfo
     * @return stdClass
     */
    private function get_course_info(array $courseinfo): stdClass {
        global $PAGE, $OUTPUT;

        /** @var core_course_renderer $courserenderer */
        $courserenderer = $PAGE->get_renderer('course');

        $course = $courseinfo['course'];
        $category = core_course_category::get($course->category, IGNORE_MISSING);
        $data = new stdClass();
        $data->courseid = $course->id;
        $data->name = $course->fullname;
        $data->completionenabled = $courseinfo['completionenabled'];
        $data->isenrolled = $courseinfo['isenrolled'];
        $data->progress = round($courseinfo['progress']);
        $data->courseinfo = $courserenderer->course_info_box($course);
        $data->courseinfoimage = course_summary_exporter::get_course_image($course)
            ?: $OUTPUT->get_generated_image_for_id($course->id);
        $data->category = isset($category) ? $category->get_formatted_name() : '';
        $data->url = $data->isenrolled ? new moodle_url('/course/view.php', ['id' => $course->id]) : '';
        return $data;
    }
}
