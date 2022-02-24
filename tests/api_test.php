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

use advanced_testcase;
use completion_completion;

/**
 * Tests for the api class methods.
 *
 * @covers      \block_mylearning\api
 * @package     block_mylearning
 * @copyright   2022 Moodle Pty Ltd <support@moodle.com>
 * @author      2022 Odei Alba <odei.alba@moodle.com>
 * @license     Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */
class api_test extends advanced_testcase {

    /**
     * setUp.
     */
    public function setUp(): void {
        $this->resetAfterTest();
    }

    /**
     * Test get_user_courses_completion api method.
     */
    public function test_get_user_courses_completion(): void {
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($user->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($user->id, $course2->id, 'student');

        $startedtime = time() - DAYSECS;
        $completiontime = time();
        $ccompletion = new completion_completion(['course' => $course2->id, 'userid' => $user->id]);
        $ccompletion->mark_inprogress($startedtime);
        $ccompletion->mark_complete($completiontime);

        $completions = api::get_user_courses_completion([$course1, $course2], $user->id);

        $this->assertEqualsCanonicalizing([$course1->id, $course2->id], array_keys($completions));
        $this->assertNull($completions[$course1->id]->timecompleted);
        $this->assertEquals($completiontime, $completions[$course2->id]->timecompleted);
    }

    /**
     * Test get_user_courses_progress api method.
     */
    public function test_get_user_courses_progress(): void {
        $course1 = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $course2 = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($user->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($user->id, $course2->id, 'student');

        $startedtime1 = time() - DAYSECS;
        $startedtime2 = time();
        $ccompletion1 = new completion_completion(['course' => $course1->id, 'userid' => $user->id]);
        $ccompletion1->mark_inprogress($startedtime1);
        $ccompletion1->mark_complete($startedtime2);
        $ccompletion2 = new completion_completion(['course' => $course2->id, 'userid' => $user->id]);
        $ccompletion2->mark_inprogress($startedtime2);

        $progresses = api::get_user_courses_progress($user->id, [$course1, $course2]);

        $this->assertEqualsCanonicalizing([$course1->id, $course2->id], array_keys($progresses));
        $this->assertEquals(100, $progresses[$course1->id]);
        $this->assertEquals(0, $progresses[$course2->id]);
    }

    /**
     * Test get_all_user_course_startdates api method.
     */
    public function test_get_all_user_course_startdates(): void {
        global $DB;
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($user->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($user->id, $course2->id, 'student');

        $startedtime1 = time();

        $enrolids = $DB->get_fieldset_select('enrol', 'id', 'courseid=?', [$course1->id]);
        list($enrolidsql, $enrolidparams) = $DB->get_in_or_equal($enrolids, SQL_PARAMS_NAMED);
        $conditionssql = "userid = :userid AND enrolid $enrolidsql";
        $conditionsparams = ['userid' => $user->id] + $enrolidparams;
        $userenrolments = $DB->get_records_select('user_enrolments', $conditionssql, $conditionsparams);
        $userenrolment = reset($userenrolments);
        $userenrolment->timestart = $startedtime1;
        $DB->update_record('user_enrolments', $userenrolment);

        $startdates = api::get_all_user_course_startdates($user->id, [$course1->id, $course2->id]);

        $this->assertEqualsCanonicalizing([$course1->id], array_keys($startdates));
        $this->assertEquals($startdates[$course1->id]->timestart, $startedtime1);

        $startedtime2 = time() + 10;

        $enrolids2 = $DB->get_fieldset_select('enrol', 'id', 'courseid=?', [$course2->id]);
        list($enrolid2sql, $enrolid2params) = $DB->get_in_or_equal($enrolids2, SQL_PARAMS_NAMED);
        $conditionssql2 = "userid = :userid AND enrolid $enrolid2sql";
        $conditionsparams2 = ['userid' => $user->id] + $enrolid2params;
        $userenrolments2 = $DB->get_records_select('user_enrolments', $conditionssql2, $conditionsparams2);
        $userenrolment2 = reset($userenrolments2);
        $userenrolment2->timestart = $startedtime2;
        $DB->update_record('user_enrolments', $userenrolment2);

        $startdates2 = api::get_all_user_course_startdates($user->id, [$course1->id, $course2->id]);

        $this->assertEqualsCanonicalizing([$course1->id, $course2->id], array_keys($startdates2));
        $this->assertEquals($startdates2[$course1->id]->timestart, $startedtime1);
        $this->assertEquals($startdates2[$course2->id]->timestart, $startedtime2);
    }

    /**
     * Test get_last_course_access api method.
     */
    public function test_get_last_course_access(): void {
        global $DB;
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($user->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($user->id, $course2->id, 'student');

        $timeaccess1 = time();
        $timeaccess2 = time() + DAYSECS;
        $DB->insert_record('user_lastaccess',
            ['userid' => $user->id, 'courseid' => $course1->id, 'timeaccess' => $timeaccess1]);
        $DB->insert_record('user_lastaccess',
            ['userid' => $user->id, 'courseid' => $course2->id, 'timeaccess' => $timeaccess2]);

        $lastaccesses = api::get_last_course_access($user->id, [$course1->id, $course2->id]);

        $this->assertEqualsCanonicalizing([$course1->id, $course2->id], array_keys($lastaccesses));
        $this->assertEquals($lastaccesses[$course1->id]->timeaccess, $timeaccess1);
        $this->assertEquals($lastaccesses[$course2->id]->timeaccess, $timeaccess2);

        $timeaccess3 = $timeaccess2 + DAYSECS;
        $DB->set_field('user_lastaccess', 'timeaccess', $timeaccess3, [
            'userid' => $user->id,
            'courseid' => $course2->id,
        ]);

        $lastaccesses2 = api::get_last_course_access($user->id, [$course1->id, $course2->id]);

        $this->assertEquals($lastaccesses2[$course2->id]->timeaccess, $timeaccess3);
    }

    /**
     * Test get_user_accessible_courses api method.
     */
    public function test_get_user_accessible_courses(): void {
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = self::getDataGenerator()->create_course();

        self::getDataGenerator()->enrol_user($user->id, $course1->id, 'student');
        self::getDataGenerator()->enrol_user($user->id, $course2->id, 'teacher');

        $courses = api::get_user_accessible_courses($user->id);
        $courseids = array_map(function($course) {
            return $course->id;
        }, $courses);

        $this->assertCount(2, $courses);
        $this->assertEqualsCanonicalizing([$course1->id, $course2->id], $courseids);
    }
}
