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
use tool_program\persistent\program_set;

/**
 * Class for exporting program set data.
 *
 * @package    block_mylearning
 * @copyright  2018 Moodle Pty Ltd <support@moodle.com>
 * @author     2018 Mitxel Moriana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class program_set_exporter extends persistent_exporter {
    /**
     * Defines the persistent class.
     *
     * @return string
     */
    protected static function define_class(): string {
        return program_set::class;
    }

    /**
     * Related objects definition.
     *
     * @return array
     */
    protected static function define_related(): array {
        return [
            'context' => 'context',
        ];
    }

    /**
     * Other properties.
     *
     * @return array
     */
    public static function define_other_properties(): array {
        return [
            'editablename' => [
                'type' => PARAM_RAW,
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
     * Other values.
     *
     * @param \renderer_base $output
     * @return array
     */
    protected function get_other_values(\renderer_base $output): array {
        return [
            'editablename' => $this->persistent->get('name'),
        ];
    }
}
