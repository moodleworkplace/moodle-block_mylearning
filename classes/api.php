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

namespace block_mylearning;

use core_completion\progress;
use lang_string;
use stdClass;
use tool_program\api as programapi;
use tool_program\program_tree_progress;

/**
 * Class api
 *
 * @package    block_mylearning
 * @copyright  2022 Moodle Pty Ltd <support@moodle.com>
 * @author     2022 Odei Alba <odei.alba@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class api {

    /**
     * Get courses completions for a given user
     *
     * @param stdClass[] $courses
     * @param int $userid
     * @return array
     */
    public static function get_user_courses_completion(array $courses, int $userid): array {
        global $DB, $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        $courseids = array_column($courses, 'id');
        [$insql, $inparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'cid', true, 0);

        $sql = "SELECT course, timecompleted
                FROM {course_completions}
                WHERE course $insql AND userid = :userid";
        $params = $inparams + ['userid' => $userid];
        $completions = $DB->get_records_sql($sql, $params);
        $coursecompletions = [];
        foreach ($courses as $course) {
            $completion = new \completion_info($course);
            $coursecompletions[$course->id] = (object) [
                'timecompleted' => $completions[$course->id]->timecompleted ?? null,
                'completionenabled' => $completion->is_enabled(),
            ];
        }
        return $coursecompletions;
    }

    /**
     * Returns list of progress percentage for each course from a given user
     *
     * @param int $userid
     * @param array $courses
     * @param array|null $coursescompletion
     * @return array
     */
    public static function get_user_courses_progress(int $userid, array $courses, ?array $coursescompletion = null): array {
        $coursesprogress = [];

        if (!$coursescompletion) {
            $coursescompletion = self::get_user_courses_completion($courses, $userid);
        }

        foreach ($courses as $course) {
            $courseprogress = progress::get_course_progress_percentage($course, $userid) ?? 0;
            // Adjust progress to maximum 95% if course is not completed.
            if (is_null($coursescompletion[$course->id]->timecompleted)) {
                $courseprogress = min($courseprogress, 95);
            }
            $coursesprogress[$course->id] = (float)$courseprogress;
        }
        return $coursesprogress;
    }

    /**
     * Get all user start dates for the courses
     *
     * @param int $userid
     * @param array $courseids
     * @return array
     */
    public static function get_all_user_course_startdates(int $userid, array $courseids): array {
        global $DB;
        if (!$courseids) {
            return [];
        }
        $courseids = array_unique($courseids);
        [$sqlids, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'course');

        $sql = "SELECT e.courseid, MIN(ue.timestart) as timestart
                  FROM {enrol} e
                  JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.userid = :userid
                  JOIN {course} c ON c.id = e.courseid
                 WHERE ue.status = :active
                   AND e.status = :enabled
                   AND ue.timestart >= c.startdate
                   AND c.id $sqlids
                   GROUP BY e.courseid";
        $params['userid'] = $userid;
        $params['active'] = ENROL_USER_ACTIVE;
        $params['enabled'] = ENROL_INSTANCE_ENABLED;

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Get last access timestamp for the user for multiple courses at once
     *
     * @param int $userid
     * @param array $courseids
     * @return array
     */
    public static function get_last_course_access(int $userid, array $courseids): array {
        global $DB;
        if (!$courseids) {
            return [];
        }
        $courseids = array_unique($courseids);
        [$sql, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'course');
        return $DB->get_records_select('user_lastaccess', 'userid=:user AND courseid '.$sql,
            $params + ['user' => $userid], '', 'courseid, timeaccess');
    }

    /**
     * Returns a list of accessible courses for current user excluding courses enrolled only with the enrol program plugin.
     *
     * @return stdClass[]
     */
    public static function get_user_accessible_courses(): array {
        global $CFG, $USER;
        require_once($CFG->dirroot . '/course/lib.php');
        $hiddencourses = get_hidden_courses_on_timeline();
        if (class_exists(programapi::class)) {
            $workplaceexcludedcourses = programapi::get_course_ids_with_only_enrol_program_instance($USER->id);
            $hiddencourses = array_unique(array_merge($hiddencourses, $workplaceexcludedcourses));
        }
        return enrol_get_my_courses('summary, summaryformat, enddate', null, 0, [], false, 0, $hiddencourses);
    }

    /**
     * Get all programs tree progress.
     *
     * @param array $programs
     * @param int $userid
     * @return array
     */
    public static function get_programs_tree_progress(array $programs, int $userid): array {
        $programstreeprogress = [];
        if ($programs) {
            foreach ($programs as $program) {
                $programstreeprogress[$program->get('id')] = new program_tree_progress($program, $userid);
            }
        }
        return $programstreeprogress;
    }

    /**
     * Get the status filters options.
     *
     * @return lang_string[]
     */
    public static function get_status_filter_options(): array {
        return [
            'all' => new lang_string('all', 'block_mylearning'),
            'courses' => new lang_string('courses'),
            'programs' => new lang_string('programs', 'block_mylearning'),
            'notcompleted' => new lang_string('notcompleted', 'block_mylearning'),
            'completed' => new lang_string('completed', 'block_mylearning'),
        ];
    }
}
