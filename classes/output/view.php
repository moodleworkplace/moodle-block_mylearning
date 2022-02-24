<?php
// This file is part of Moodle Workplace https://moodle.com/workplace based on Moodle
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
//
// Moodle Workplace™ Code is the collection of software scripts
// (plugins and modifications, and any derivations thereof) that are
// exclusively owned and licensed by Moodle under the terms of this
// proprietary Moodle Workplace License ("MWL") alongside Moodle's open
// software package offering which itself is freely downloadable at
// "download.moodle.org" and which is provided by Moodle under a single
// GNU General Public License version 3.0, dated 29 June 2007 ("GPL").
// MWL is strictly controlled by Moodle Pty Ltd and its certified
// premium partners. Wherever conflicting terms exist, the terms of the
// MWL are binding and shall prevail.

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
use tool_certification\api as certificationapi;
use tool_program\api as programapi;

/**
 * Class view
 *
 * @package    block_mylearning
 * @copyright  2018 Moodle Pty Ltd <support@moodle.com>
 * @author     2018 Mitxel Moriana
 * @license    Moodle Workplace License, distribution is restricted, contact support@moodle.com
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
        $userpreferencesstatus = !$isprogrampluginincluded && $userpreferencesstatus === 'programs'
                ? 'all'
                : $userpreferencesstatus;
        $userpreferencesview = get_user_preferences('block_mylearning_view_filter', $config->display);
        $userpreferencessort = get_user_preferences('block_mylearning_sort_filter', $config->sort);

        // Default empty arrays when plrogram plugin is not installed.
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
                $certifications = certificationapi::get_certifications_by_userid($this->userid);
                $certallocations = certificationapi::get_user_allocations($this->userid);
            }
            $programs = programapi::get_user_accessible_programs($this->userid);
            $programstreeprogress = programapi::get_programs_tree_progress($programs, $this->userid);
            $programsallocations = programapi::get_user_allocations($this->userid);

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
        $courses = api::get_user_accessible_courses($isprogrampluginincluded);
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
            $statuses = certificationapi::get_user_allocation_status($certificationid, $this->userid);
            $status[$allocationid] = $statuses[0]['statusint'] ?? -1;
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
            $completion = certificationapi::get_last_completion_record($this->userid, $certid);
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
