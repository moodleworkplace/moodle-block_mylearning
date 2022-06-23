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
use tool_certification\certification;
use tool_certification\certification_user;
use tool_program\persistent\program;
use tool_program\persistent\program_user;
use tool_program\program_tree_progress;

/**
 * Class for exporting field data.
 *
 * @package    block_mylearning
 * @copyright  2018 Moodle Pty Ltd <support@moodle.com>
 * @author     2018 Mitxel Moriana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class view_exporter extends exporter {
    /**
     * Returns a list of objects that are related.
     *
     * @return array
     */
    protected static function define_related(): array {
        return [
            'context' => 'context',
            'user' => 'stdClass',
            'programs' => '\\tool_program\\persistent\\program[]',
            'courses' => 'stdClass[]',
            'coursesprogress' => 'float[]',
            'coursescompletion' => 'stdClass[]',
            'lastcourseaccess' => 'stdClass[]?',
            'programstreeprogress' => '\\tool_program\\program_tree_progress[]',
            'programsallocations' => '\\tool_program\\persistent\\program_user[]',
            'certifications' => '\\tool_certification\\certification[]',
            'certificationscompletion' => '\\tool_certification\\certification_completion[]',
            'certificationsallocations' => '\\tool_certification\\certification_user[]',
            'certificationsallocationsstatus' => 'int[]',
            'statusfilterstr' => 'string',
            'statusfilterval' => 'string',
            'showfilters' => 'bool',
            'programsenrolledcourses' => 'array[]?',
            'view' => 'string?',
            'sort' => 'string?',
            'coursesmodals' => 'stdClass[]',
            'coursestartdates' => 'stdClass[]?',
            'isdashboard' => 'bool',
            'isprogrampluginincluded' => 'bool',
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
            'learningelements' => [
                'type' => PARAM_RAW,
            ],
            'viewasmode' => [
                'type' => PARAM_BOOL,
            ],
            'statusfilterstr' => [
                'type' => PARAM_TEXT,
            ],
            'statusfilterval' => [
                'type' => PARAM_TEXT,
            ],
            'showfilters' => [
                'type' => PARAM_BOOL,
            ],
            'view' => [
                'type' => PARAM_TEXT,
            ],
            'sort' => [
                'type' => PARAM_TEXT,
            ],
            'coursesmodals' => [
                'type' => PARAM_RAW,
            ],
            'isprogrampluginincluded' => [
                'type' => PARAM_BOOL,
            ]
        ];
    }

    /**
     * Other values
     *
     * @param renderer_base $output
     * @return array
     */
    protected function get_other_values(renderer_base $output): array {
        /** @var context $context */
        $context = $this->related['context'];
        /** @var stdClass $user */
        $user = $this->related['user'];
        /** @var int $userid */
        $userid = (int) $user->id;
        /** @var program[] $programs */
        $programs = $this->related['programs'];
        /** @var stdClass[] $courses */
        $courses = $this->related['courses'];
        /** @var stdClass[] $coursesprogress */
        $coursesprogress = $this->related['coursesprogress'];
        /** @var stdClass[] $coursescompletion */
        $coursescompletion = $this->related['coursescompletion'];
        /** @var array $lastcourseaccess */
        $lastcourseaccess = $this->related['lastcourseaccess'];
        /** @var program_tree_progress[] $programstreeprogress */
        $programstreeprogress = $this->related['programstreeprogress'];
        /** @var program_user[] $programsallocations */
        $programsallocations = $this->related['programsallocations'];
        /** @var certification[] $certifications */
        $certifications = $this->related['certifications'];
        /** @var array $certificationscompletion */
        $certificationscompletion = $this->related['certificationscompletion'];
        /** @var certification_user[] $certificationsallocations */
        $certificationsallocations = $this->related['certificationsallocations'];
        /** @var array $certificationsallocationsstatus */
        $certificationsallocationsstatus = $this->related['certificationsallocationsstatus'];
        /** @var string $statusfilterstr */
        $statusfilterstr = $this->related['statusfilterstr'];
        /** @var string $statusfilterval */
        $statusfilterval = $this->related['statusfilterval'];
        /** @var bool $showfilters */
        $showfilters = $this->related['showfilters'];
        /** @var array $programsenrolledcourses */
        $programsenrolledcourses = $this->related['programsenrolledcourses'];
        /** @var string $view */
        $view = $this->related['view'];
        /** @var string $sort */
        $sort = $this->related['sort'];
        /** @var array $coursesmodals */
        $coursesmodals = $this->related['coursesmodals'];
        /** @var array $coursestartdates */
        $coursestartdates = $this->related['coursestartdates'];
        /** @var bool $isdashboard */
        $isdashboard = $this->related['isdashboard'];
        /** @var bool $isprogrampluginincluded */
        $isprogrampluginincluded = $this->related['isprogrampluginincluded'];

        // Learning elements can be programs or individual courses.
        $learningelements = [];

        // Uniqueid is generated and used in all related templates, so collapse/toggle elements work correctly
        // with multiple instances. For example using 'Learning' tab alongside 'Learning' block.
        $uniqueid = random_string(10);

        if ($isprogrampluginincluded) {
            // Add programs to learning elements.
            foreach ($programs as $program) {
                // Fetch data specifically related to this program.
                $programid = (int) $program->get('id');
                $relatedprogramallocations = $this->fetch_related_program_allocations($programsallocations, $programid);
                $relatedcertifications = $certifications;
                $relatedcertallocations = $this->fetch_related_cert_allocations($certificationsallocations, $relatedcertifications);

                $relateddata = [
                    'context' => $context,
                    'uniqueid' => $uniqueid,
                    'user' => $user,
                    'program' => $program,
                    'programtreeprogress' => $programstreeprogress[$programid],
                    'programallocations' => $relatedprogramallocations,
                    'certifications' => $relatedcertifications,
                    'certificationscompletion' => $certificationscompletion,
                    'certificationallocations' => $relatedcertallocations,
                    'certificationsallocationsstatus' => $certificationsallocationsstatus,
                    'programenrolledcourses' => $programsenrolledcourses[$programid] ?? null,
                    'lastcourseaccess' => $lastcourseaccess,
                ];
                $exporter = new program_exporter(null, $relateddata);

                $learningelements[] = $exporter->export($output);
            }
        }

        // Add non program courses to learning elements.
        foreach ($courses as $course) {
            $startdate = $coursestartdates[$course->id]->timestart ?? 0;
            $relateddata = [
                'context' => $context,
                'uniqueid' => $uniqueid,
                'course' => $course,
                'iscompleted' => !is_null($coursescompletion[$course->id]->timecompleted),
                'completionenabled' => (bool)$coursescompletion[$course->id]->completionenabled,
                'progress' => $coursesprogress[$course->id],
                'lastcourseaccess' => $lastcourseaccess,
                'startdate' => (int)$startdate,
            ];
            $exporter = new course_exporter(null, $relateddata);
            $learningelements[] = $exporter->export($output);
        }

        // Order programs and courses by due date, name or lastaccess.
        if ($learningelements) {
            $sortduedate = [];
            $sortname = [];
            $lastaccess = [];
            foreach ($learningelements as $key => $row) {
                $lastaccess[$key] = (int)$row->lastaccess; // If never accessed, assume 0, it will move them to the bottom.
                $sortname[$key] = strtolower($row->fullname);
                $sortduedate[$key] = $row->lowestduedate;
            }
            if ($sort === 'programname') {
                // Sort by name.
                array_multisort($sortname, SORT_ASC, $learningelements);
            } else if ($sort === 'duedate') {
                // Sort by conclusion date.
                array_multisort($sortduedate, SORT_ASC, $lastaccess, SORT_DESC, $learningelements);
            } else {
                // Sort by last accessed time, and in case it's the same by name like in program_overview JS file.
                array_multisort($lastaccess, SORT_DESC, $sortname, SORT_ASC, $learningelements);
            }
        }

        return [
            'id' => $userid,
            'uniqueid' => $uniqueid,
            'learningelements' => array_values($learningelements),
            'viewasmode' => !$isdashboard,
            'statusfilterstr' => $statusfilterstr,
            'statusfilterval' => $statusfilterval,
            'showfilters' => $showfilters,
            'view' => $view,
            'sort' => $sort,
            'coursesmodals' => $coursesmodals,
            'isprogrampluginincluded' => $isprogrampluginincluded
        ];
    }

    /**
     * Fetches allocations related to the passed program id and removes them from the original list.
     *
     * @param program_user[] $allocations
     * @param int $programid
     * @return program_user[]
     */
    private function fetch_related_program_allocations(&$allocations, int $programid): array {
        $relatedallocations = [];
        foreach ($allocations as $key => $allocation) {
            if ($programid === (int) $allocation->get('programid')) {
                $relatedallocations[$allocation->get('id')] = $allocation;
                unset($allocations[$key]);
            }
        }

        return $relatedallocations;
    }

    /**
     * Fetches certification allocations related to the passed program allocations and removes them from the original list.
     *
     * @param certification_user[] $usercertifications
     * @param certification[] $relatedcertifications
     * @return certification_user[]
     */
    private function fetch_related_cert_allocations(&$usercertifications, $relatedcertifications): array {
        $certificationids = array_map(static function($relatedcertification) {
            return $relatedcertification->get('id');
        }, $relatedcertifications);

        $programcertificationusers = [];
        foreach ($usercertifications as $key => $usercertification) {
            if (in_array($usercertification->get('certificationid'), $certificationids, true)) {
                $programcertificationusers[$usercertification->get('id')] = $usercertification;
            }
        }

        return $programcertificationusers;
    }
}
