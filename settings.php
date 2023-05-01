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
 * My learning block settings
 *
 * @package    block_mylearning
 * @copyright  2022 Moodle Pty Ltd <support@moodle.com>
 * @author     2022 Odei Alba <odei.alba@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

use block_mylearning\api;

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $optionsstatus = api::get_status_filter_options();
    $settings->add(new admin_setting_configselect('block_mylearning/show', new lang_string('show'),
                       new lang_string('settingsdescription', 'block_mylearning', new lang_string('show')),
                       'all', $optionsstatus));

    $optionssort = [
        'mylearningname' => new lang_string('name'),
        'duedate' => new lang_string('conclusiondate', 'block_mylearning'),
        'lastaccess' => new lang_string('lastaccessed', 'block_mylearning'),
    ];
    $settings->add(new admin_setting_configselect('block_mylearning/sort', new lang_string('sortby'),
                       new lang_string('settingsdescription', 'block_mylearning', new lang_string('sortby')),
                       'lastaccess', $optionssort));

    $optionsview = [
        'viewcards' => new lang_string('expanded', 'block_mylearning'),
        'viewlist' => new lang_string('collapsed', 'block_mylearning'),
    ];
    $settings->add(new admin_setting_configselect('block_mylearning/display', new lang_string('display', 'block_mylearning'),
                       new lang_string('settingsdescription', 'block_mylearning', new lang_string('display', 'block_mylearning')),
                       'viewcards', $optionsview));
}
