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

namespace block_mylearning\external;

use context;
use core\external\exporter;
use core_tag_tag;
use renderer_base;
use stdClass;
use tool_certification\certification;
use tool_certification\certification_completion;
use tool_certification\certification_user;
use tool_program\api as programapi;
use tool_program\constants;
use tool_program\persistent\program;
use tool_program\persistent\program_user;
use tool_program\program_tree_progress;

/**
 * Class for exporting program data to the mylearning overview view.
 *
 * @package    block_mylearning
 * @copyright  2019 Moodle Pty Ltd <support@moodle.com>
 * @author     2019 Mitxel Moriana <mitxel@tresipunt.com>
 * @license    Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */
class program_exporter extends exporter {
    /**
     * Returns a list of objects that are related.
     *
     * @return array
     */
    protected static function define_related(): array {
        return [
            'context' => 'context',
            'uniqueid' => 'string',
            'user' => 'stdClass',
            'program' => program::class,
            'programtreeprogress' => program_tree_progress::class,
            'programallocations' => '\\tool_program\\persistent\\program_user[]',
            'certifications' => '\\tool_certification\\certification[]',
            'certificationscompletion' => '\\tool_certification\\certification_completion[]',
            'certificationallocations' => '\\tool_certification\\certification_user[]',
            'certificationsallocationsstatus' => 'int[]',
            'programenrolledcourses' => 'stdclass[]?',
            'lastcourseaccess' => 'stdClass[]?',
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
            'description' => [
                'type' => PARAM_RAW,
            ],
            'image' => [
                'type' => PARAM_RAW,
            ],
            'hastags' => [
                'type' => PARAM_BOOL,
            ],
            'tags' => [
                'type' => PARAM_RAW,
            ],
            'customfields' => [
                'type' => PARAM_RAW,
            ],
            'items' => [
                'type' => PARAM_RAW,
            ],
            'origin' => [
                'type' => PARAM_RAW,
            ],
            'status' => [
                'type' => PARAM_TEXT,
            ],
            'progress' => [
                'type' => PARAM_INT,
            ],
            'iscompleted' => [
                'type' => PARAM_BOOL,
            ],
            'calltoaction' => [
                'type' => PARAM_TEXT,
            ],
            'calltoactionstr' => [
                'type' => PARAM_TEXT,
            ],
            'ongoingcourseid' => [
                'type' => PARAM_INT,
            ],
            'ongoingisenrolled' => [
                'type' => PARAM_BOOL,
            ],
            'showduedate' => [
                'type' => PARAM_BOOL,
            ],
            'closetoduedate' => [
                'type' => PARAM_BOOL,
            ],
            'lowestduedate' => [
                'type' => PARAM_INT,
            ],
            'isprogram' => [
                'type' => PARAM_BOOL,
            ],
            'certificationcount' => [
                'type' => PARAM_INT,
            ],
            'lastaccess' => [
                'type' => PARAM_INT
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
        /** @var context $context */
        $context = $this->related['context'];
        /** @var stdClass $user */
        $user = $this->related['user'];
        /** @var program $program */
        $program = $this->related['program'];
        /** @var program_user[] $programallocations */
        $programallocations = $this->related['programallocations'];
        /** @var certification[] $certifications */
        $certifications = $this->related['certifications'];
        /** @var array $certificationscompletion */
        $certificationscompletion = $this->related['certificationscompletion'];
        /** @var certification_user[] $certificationallocations */
        $certificationallocations = $this->related['certificationallocations'];
        /** @var array $certificationsallocationsstatus */
        $certificationsallocationsstatus = $this->related['certificationsallocationsstatus'];
        /** @var program_tree_progress $programtreeprogress */
        $programtreeprogress = $this->related['programtreeprogress'];
        /** @var array $programenrolledcourses */
        $programenrolledcourses = $this->related['programenrolledcourses'];

        $exporteddata = new stdClass();
        $exporteddata->uniqueid = $this->related['uniqueid'];
        $this->export_program_details($program, $exporteddata, $context);
        $this->export_program_customfields($program, $exporteddata);
        $this->export_program_image($program, $exporteddata);
        $this->export_program_contents_and_call_to_action(
            $programtreeprogress,
            $exporteddata,
            $context,
            $user,
            $output,
            $programenrolledcourses
        );
        $this->export_program_allocation_origins($program, $exporteddata, $programallocations, $certifications,
            $certificationallocations, $certificationsallocationsstatus, $certificationscompletion);
        $this->export_program_tags($program, $exporteddata);
        $exporteddata->lowestduedate = array_reduce($exporteddata->origin, function($min, $alloc) {
            if ((int)$alloc->duedatetimestamp > 0) {
                return min($min, (int)$alloc->duedatetimestamp);
            } else {
                return $min;
            }
        }, constants::MAX_DATE);

        $exporteddata->isprogram = true;
        $exporteddata->lastaccess = $this->export_program_lastaccess($programenrolledcourses);

        return (array) $exporteddata;
    }

    /**
     * Fetches the user certification related to the passed certification id and removes it from the original list.
     *
     * @param certification_user[] $certificationallocations
     * @param int $certificationid
     * @return certification_user|null
     */
    private function fetch_related_certification_allocation(&$certificationallocations, int $certificationid): ?certification_user {
        $relatedallocation = null;
        foreach ($certificationallocations as $key => $certificationallocation) {
            if ($certificationid === (int) $certificationallocation->get('certificationid')) {
                $relatedallocation = $certificationallocation;
                unset($certificationallocations[$key]);
                break;
            }
        }

        return $relatedallocation;
    }

    /**
     * Export basic program details.
     *
     * @param program $program
     * @param stdClass $exporteddata
     * @param context $context
     */
    private function export_program_details(program $program, stdClass $exporteddata, context $context): void {
        $exporteddata->id = $program->get('id');
        $exporteddata->fullname = $program->get_formatted_name();
        $description = file_rewrite_pluginfile_urls($program->get('description'), 'pluginfile.php', $context->id,
            'tool_program', 'program_description', $program->get('id'));
        $exporteddata->description = format_text($description, $program->get('descriptionformat'));
    }

    /**
     * Export program customfields.
     *
     * @param program $program
     * @param stdClass $exporteddata
     */
    private function export_program_customfields(program $program, stdClass $exporteddata): void {
        global $PAGE;

        $customfields = programapi::get_program_customfields($program->get('id'));
        $exporteddata->customfields = [];
        $output = $PAGE->get_renderer('core_customfield');
        foreach ($customfields as $customfield) {
            $content = $output->render($customfield);
            $exporteddata->customfields[] = $content;
        }
    }

    /**
     * Export program tags.
     *
     * @param program $program
     * @param stdClass $exporteddata
     */
    private function export_program_tags(program $program, stdClass $exporteddata): void {
        $tags = core_tag_tag::get_item_tags_array('tool_program', 'tool_program', $program->get('id'));
        foreach ($exporteddata->origin as $origin) {
            if (isset($origin->certificationid)) {
                $certificationtags = core_tag_tag::get_item_tags_array('tool_certification', 'tool_certification',
                    $origin->certificationid);
                $tags = array_merge($tags, $certificationtags);
            }
        }
        $exporteddata->tags = array_values(array_unique($tags));
        $exporteddata->hastags = !empty($exporteddata->tags);
    }

    /**
     * Export program contents (child items) with completion and locked information and the program call to action data.
     *
     * @param program_tree_progress $programtreeprogress
     * @param stdClass $exporteddata
     * @param context $context
     * @param stdClass $user
     * @param renderer_base $output
     * @param array|null $programenrolledcourses
     */
    private function export_program_contents_and_call_to_action(program_tree_progress $programtreeprogress, stdClass $exporteddata,
            context $context, stdClass $user, renderer_base $output, ?array $programenrolledcourses): void {
        // Export program contents (program items) with completion and locked data.
        $exporter = new program_tree_progress_exporter(null, [
            'context' => $context,
            'user' => $user,
            'programtreeprogress' => $programtreeprogress,
            'programenrolledcourses' => $programenrolledcourses
        ]);
        $baseset = $exporter->export($output)->parentset;
        $exporteddata->items = $baseset->items;

        // Export program progress data.
        $exporteddata->status = $baseset->completiontypestr . ' (' . $baseset->completeditems . '/' . $baseset->totalitems . ')';
        $exporteddata->progress = (int)$baseset->progress;
        $exporteddata->iscompleted = $baseset->iscompleted;

        // Export program call to action data.
        $exporteddata->calltoaction = $baseset->calltoaction;
        $exporteddata->ongoingcourseid = $baseset->ongoingcourseid;
        $exporteddata->ongoingisenrolled = $baseset->ongoingisenrolled;
        $exporteddata->calltoactionstr = $baseset->calltoactionstr;
    }

    /**
     * Exports program image data.
     *
     * @param program $program
     * @param stdClass $exportedprogram
     */
    private function export_program_image(program $program, stdClass $exportedprogram): void {
        $exportedprogram->image = $program->get_image_url();
        // If program has no image set just show random image pattern.
        if (!$exportedprogram->image) {
            $exportedprogram->image = programapi::get_program_pattern($program->get('id'));
        }
    }

    /**
     * Exports program allocation origin data.
     *
     * @param program $program
     * @param stdClass $exporteddata
     * @param program_user[] $programallocations
     * @param certification[] $relatedcertifications
     * @param certification_user[] $relatedcertificationallocations
     * @param array $certificationsallocationsstatus
     * @param array $certificationscompletion
     */
    private function export_program_allocation_origins(program $program, stdClass $exporteddata, $programallocations,
        $relatedcertifications, $relatedcertificationallocations, $certificationsallocationsstatus,
        $certificationscompletion): void {

        $exporteddata->origin = [];
        $exporteddata->showduedate = 0; // If due date in program is not set, then hide due date on dashboard.
        $exporteddata->closetoduedate = 0;
        $exporteddata->certificationcount = 0;
        foreach ($programallocations as $programallocation) {
            if (constants::STATUS_OVERRIDE_SUSPENDED === (int) $programallocation->get('status')) {
                // Do not show information about suspended allocations.
                continue;
            }

            $allocationsource = new stdClass();
            $allocationsource->duedatetimestamp = (int) $programallocation->get('duedate');
            // If Due date it is not set, we show a "Not set" string.
            if (0 === (int) $programallocation->get('duedate')) {
                $allocationsource->formatedduedate = get_string('notset', 'tool_program');
                $allocationsource->formatedduedatehide = 1;
            } else {
                $allocationsource->formatedduedate = userdate($programallocation->get('duedate'),
                    get_string('strftimedatefullshort'));
                $allocationsource->formatedduedatehide = 0;
            }

            $certificationid = (int) $programallocation->get('certificationid');
            if (0 !== $certificationid) {
                $certification = $relatedcertifications[$certificationid];
                if ($certification->is_archived()) {
                    // Do not show information about certification allocations when the certification is archived.
                    continue;
                }
                $certificationallocation = $this->fetch_related_certification_allocation(
                    $relatedcertificationallocations, $certificationid);
                if (!$certificationallocation) {
                    continue;
                }

                // Do not show information about 'suspended' or 'future allocation' certification allocations.
                $allocationstatus = (int) $certificationallocation->get('status');
                $statusissuspended = (\tool_certification\constants::STATUS_OVERRIDE_SUSPENDED === $allocationstatus);
                $statusisfutureallocation = (time() < (int) $programallocation->get('startdate'));
                if ($statusissuspended || $statusisfutureallocation) {
                    continue;
                }

                $allocationsource->fullname = format_string($certification->get('fullname'));
                $allocationsource->certificationid = $certificationid;
                $allocationsource->certificationduedate = $programallocation->get('duedate');
                $certificationstatus = $certificationsallocationsstatus[$certificationallocation->get('id')];
                $certificationcompletion = $certificationscompletion[$certification->get('id')] ?? null;
                [$datemsg, $alertstyle, $iconstyle] = $this->get_certification_allocation_messages($certificationstatus,
                    $certificationcompletion, (int)$allocationsource->certificationduedate, $certification);
                $allocationsource->alertstyle = $alertstyle;
                $allocationsource->iconstyle = $iconstyle;
                $exporteddata->certificationcount += 1;
                $allocationsource->certificationmsg = $datemsg;
            } else {
                $allocationsource->fullname = $program->get_formatted_name();
            }

            if ($certificationid === 0 && $allocationsource->formatedduedatehide !== 1) {
                // If any program allocation due date is set, then show due date on dashboard.
                $exporteddata->showduedate = 1;

                // Check if due date is in less than 7 days.
                $now = time();
                if (($now <= $programallocation->get('duedate'))
                    && $programallocation->get('duedate') < ($now + WEEKSECS)) {
                    $exporteddata->closetoduedate = 1;
                }
            }

            $exporteddata->origin[] = $allocationsource;
        }
    }

    /**
     * Generates all certification allocation messages to show on programs.
     *
     * @param array $status
     * @param null|certification_completion $completion
     * @param int $allocationduedate
     * @param certification $certification
     * @return array
     */
    private function get_certification_allocation_messages($status, $completion, $allocationduedate,
                                                           certification $certification): array {
        $fullname = format_string($certification->get('fullname'), true,
            ['context' => $certification->get_context()->id, 'escape' => false]);
        // This data-region is needed for the dashboard search to search by certification name.
        $a = ['fullname' => "<span data-region=\"certificationname\">$fullname</span>"];
        switch ($status) {
            case \tool_certification\constants::STATUS_EXPIRED:
                $a['date'] = userdate($completion->get('expirydate'), get_string('strftimedatefullshort'));
                $msgstring = get_string('certificationmsgexpired', 'tool_program', $a);
                $alertstyle = 'alert alert-danger';
                $iconstyle = 'text-danger';
                break;
            case \tool_certification\constants::STATUS_CERTIFIED:
                if (0 === (int) $completion->get('expirydate')) {
                    $msgstring = get_string('certificationmsgcompleted', 'tool_program', $a);
                } else {
                    $a['date'] = userdate($completion->get('expirydate'), get_string('strftimedatefullshort'));
                    $msgstring = get_string('certificationmsgcompletedexpired', 'tool_program', $a);
                }
                $alertstyle = 'alert alert-success';
                $iconstyle = 'text-success';
                break;
            case \tool_certification\constants::STATUS_OPEN:
                if ($allocationduedate === \tool_certification\constants::DATE_NONE) {
                    $msgstring = get_string('certificationmsgduedatenotset', 'tool_program', $a);
                } else {
                    $a['date'] = userdate($allocationduedate, get_string('strftimedatefullshort'));
                    $msgstring = get_string('certificationmsgactive', 'tool_program', $a);
                }
                $alertstyle = 'alert alert-info';
                $iconstyle = 'text-info';
                break;
            case \tool_certification\constants::STATUS_OVERDUE:
            default:
                $a['date'] = userdate($allocationduedate, get_string('strftimedatefullshort'));
                $msgstring = get_string('certificationmsgoverdue', 'tool_program', $a);
                $alertstyle = 'alert alert-warning';
                $iconstyle = 'text-warning';
                break;
        }

        return [$msgstring, $alertstyle, $iconstyle];
    }

    /**
     * Return the timestamp when the program courses were last accessed
     *
     * @param array $programcourses
     * @return int|null
     */
    private function export_program_lastaccess(array $programcourses) {
        $res = null;
        foreach ($programcourses as $programcourse) {
            $courseid = $programcourse->courseid;
            $lastaccess = $this->related['lastcourseaccess'][$courseid]->timeaccess ?? null;
            if ($lastaccess !== null && ($res === null || $res < $lastaccess)) {
                $res = $lastaccess;
            }
        }
        return $res;
    }
}
