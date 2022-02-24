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

namespace block_mylearning;

use core_completion\progress;
use stdClass;
use tool_program\api as programapi;

/**
 * Class api
 *
 * @package    block_mylearning
 * @copyright  2022 Moodle Pty Ltd <support@moodle.com>
 * @author     2022 Odei Alba <odei.alba@moodle.com>
 * @license    Moodle Workplace License, distribution is restricted, contact support@moodle.com
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
     * @param bool $isprogrampluginincluded
     * @return stdClass[]
     */
    public static function get_user_accessible_courses(bool $isprogrampluginincluded = false): array {
        global $CFG, $USER;
        require_once($CFG->dirroot . '/course/lib.php');
        $hiddencourses = get_hidden_courses_on_timeline();
        $workplaceexcludedcourses = $isprogrampluginincluded
            ? programapi::get_course_ids_with_only_enrol_program_instance($USER->id)
            : [];
        $hiddencourses = array_unique(array_merge($hiddencourses, $workplaceexcludedcourses));
        return enrol_get_my_courses('summary, summaryformat, enddate', null, 0, [], false, 0, $hiddencourses);
    }

}
