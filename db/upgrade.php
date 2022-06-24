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

/**
 * Upgrade script for block_mylearning
 *
 * @package   block_mylearning
 * @copyright 2022 Moodle Pty Ltd <support@moodle.com>
 * @author    2022 Odei Alba <odei.alba@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

/**
 * Upgrade
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_block_mylearning_upgrade(int $oldversion) {
    global $DB;

    if ($oldversion < 2022040102) {
        $sql = 'SELECT *
                    FROM {user_preferences} up
                    WHERE ' . $DB->sql_like('up.name', ':name', false, false);
        $params = [
            'name' => 'tool_program_program_%_filter',
        ];
        $preferences = $DB->get_recordset_sql($sql, $params);
        $newpreferences = [];
        foreach ($preferences as $preference) {
            $newpreference = new \stdClass();
            $newpreference->userid = $preference->userid;
            $newpreference->name = str_replace('tool_program_program_', 'block_mylearning_', $preference->name);
            $newpreference->value = $preference->value;
            if (!$DB->record_exists('user_preferences', ['userid' => $newpreference->userid, 'name' => $newpreference->name])) {
                $newpreferences[] = $newpreference;
            }
        }
        $DB->insert_records('user_preferences', $newpreferences);
        $preferences->close();

        // Mylearning block savepoint reached.
        upgrade_plugin_savepoint(true, 2022040102, 'block', 'mylearning');
    }

    return true;
}
