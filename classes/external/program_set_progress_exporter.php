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

use coding_exception;
use renderer_base;
use tool_program\persistent\program_course;
use tool_program\persistent\program_set;
use tool_program\program_item;

/**
 * Class for exporting program set data.
 *
 * @property   program_course persistent
 * @package    block_mylearning
 * @copyright  2018 Moodle Pty Ltd <support@moodle.com>
 * @author     2018 Mitxel Moriana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class program_set_progress_exporter extends program_set_exporter {
    /**
     * Related objects definition.
     *
     * @return array
     */
    protected static function define_related(): array {
        return [
            'context' => 'context',
            'programitemset' => program_item::class,
        ];
    }

    /**
     * Other properties.
     *
     * @return array
     */
    public static function define_other_properties(): array {
        return parent::define_other_properties() + [
                'completeditems' => [
                    'type' => PARAM_INT,
                ],
                'completiontype' => [
                    'type' => PARAM_TEXT,
                ],
                'completiontypestr' => [
                    'type' => PARAM_TEXT,
                ],
                'iscompleted' => [
                    'type' => PARAM_BOOL,
                ],
                'level' => [
                    'type' => PARAM_INT,
                ],
                'parentsetid' => [
                    'type' => PARAM_INT,
                ],
                'progress' => [
                    'type' => PARAM_INT,
                ],
                'showprogress' => [
                    'type' => PARAM_BOOL,
                ],
                'totalitems' => [
                    'type' => PARAM_INT,
                ],
                'unlocked' => [
                    'type' => PARAM_BOOL,
                ],
                'statusmessage' => [
                    'type' => PARAM_TEXT,
                ],
                'statusmessagestr' => [
                    'type' => PARAM_TEXT,
                ],
                'weight' => [
                    'type' => PARAM_INT,
                ],
                'completion' => [
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
        /** @var program_item $programitemset */
        $programitemset = $this->related['programitemset'];
        $completiontype = $this->get_completion_type($programitemset);
        $completiontypestring = $this->get_completion_type_string($programitemset);

        $exporteddata = parent::get_other_values($output);

        $exporteddata['parentsetid'] = $programitemset->get_parent_id();
        $exporteddata['completiontype'] = $completiontype;
        $exporteddata['completiontypestr'] = $completiontypestring;
        $exporteddata['level'] = $programitemset->level;
        $exporteddata['weight'] = $programitemset->weight;
        $exporteddata['unlocked'] = $programitemset->isunlocked;
        $exporteddata['totalitems'] = $programitemset->totalitems;
        $exporteddata['completeditems'] = $programitemset->completeditems;
        $exporteddata['completion'] = $programitemset->completion;
        $exporteddata['progress'] = (int)$programitemset->progresspercentage;
        $exporteddata['iscompleted'] = false;
        $exporteddata['showprogress'] = true;
        $exporteddata['statusmessage'] = $completiontype;
        $exporteddata['statusmessagestr'] = $completiontypestring;

        if (!$programitemset->isunlocked) {
            $exporteddata['showprogress'] = false;
            $exporteddata['statusmessage'] = 'locked';
            $exporteddata['statusmessagestr'] .= ' (' . get_string('locked', 'tool_program') . ')';
        } else if ($programitemset->iscompleted) {
            $exporteddata['iscompleted'] = true;
            $exporteddata['showprogress'] = false;
            $exporteddata['statusmessage'] = 'completed';
            $exporteddata['statusmessagestr'] = get_string('completed', 'tool_program');
        }

        return $exporteddata;
    }

    /**
     * Get completion type string.
     *
     * @param program_item $set
     * @return string
     */
    private function get_completion_type_string(program_item $set): string {
        switch ($set->get_completion_criteria()) {
            case program_set::COMPLETION_ALL_IN_ANY_ORDER:
                return get_string('completeallinanyorder', 'tool_program');
            case program_set::COMPLETION_ALL_IN_ORDER:
                return get_string('completeallinorder', 'tool_program');
            case program_set::COMPLETION_AT_LEAST:
                return get_string('completeatleast', 'tool_program')
                    . ' ' . $set->get_completion_atleast();
            default:
                throw new coding_exception('Unexpected program set completion criteria');
        }
    }

    /**
     * Get completion type string.
     *
     * @param program_item $set
     * @return string
     */
    private function get_completion_type(program_item $set): string {
        switch ($set->get_completion_criteria()) {
            case program_set::COMPLETION_ALL_IN_ANY_ORDER:
                return 'completeallinanyorder';
            case program_set::COMPLETION_ALL_IN_ORDER:
                return 'completeallinorder';
            case program_set::COMPLETION_AT_LEAST:
                return 'completeatleast';
            default:
                throw new coding_exception('Unexpected program set completion criteria');
        }
    }
}
