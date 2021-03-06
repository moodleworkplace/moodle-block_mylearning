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

namespace block_mylearning\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;

/**
 * Privacy Subsystem implementation for block_mylearning.
 *
 * @package    block_mylearning
 * @copyright  2021 Moodle Pty Ltd <support@moodle.com>
 * @author     2021 Mikel Martín <mikel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class provider implements
    // This plugin has data.
    \core_privacy\local\metadata\provider,

    // This plugin stores data in user preferences.
    \core_privacy\local\request\user_preference_provider {

    /**
     * Return the fields which contain personal data.
     *
     * @param collection $collection a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $collection): collection {
        // Mylearning block user preferences filter.
        $collection->add_user_preference('block_mylearning_status_filter',
            'privacy:metadata:preference:block_mylearning_status_filter');
        $collection->add_user_preference('block_mylearning_sort_filter',
            'privacy:metadata:preference:block_mylearning_sort_filter');
        $collection->add_user_preference('block_mylearning_view_filter',
            'privacy:metadata:preference:block_mylearning_view_filter');

        return $collection;
    }

    /**
     * Export all user preferences for the plugin
     *
     * @param int $userid
     * @return void
     */
    public static function export_user_preferences(int $userid): void {
        $status = get_user_preferences('block_mylearning_status_filter', null, $userid);
        if ($status) {
            $str = get_string('privacy:metadata:preference:block_mylearning_status_filter', 'block_mylearning');
            writer::export_user_preference('block_mylearning', 'block_mylearning_status_filter', $status, $str);
        }
        $sort = get_user_preferences('block_mylearning_sort_filter', null, $userid);
        if ($sort) {
            $str = get_string('privacy:metadata:preference:block_mylearning_sort_filter', 'block_mylearning');
            writer::export_user_preference('block_mylearning', 'block_mylearning_sort_filter', $sort, $str);
        }
        $view = get_user_preferences('block_mylearning_view_filter', null, $userid);
        if ($view) {
            $str = get_string('privacy:metadata:preference:block_mylearning_view_filter', 'block_mylearning');
            writer::export_user_preference('block_mylearning', 'block_mylearning_view_filter', $view, $str);
        }
    }
}
