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

/**
 * My learning block settings
 *
 * @package    block_mylearning
 * @copyright  2022 Moodle Pty Ltd <support@moodle.com>
 * @author     2022 Odei Alba <odei.alba@moodle.com>
 * @license    Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $optionsstatus = [
        'all' => new lang_string('all', 'tool_program'),
        'courses' => new lang_string('courses'),
        'programs' => new lang_string('programs', 'tool_program'),
        'notcompleted' => new lang_string('notcompleted', 'tool_program'),
        'completed' => new lang_string('completed', 'tool_program'),
    ];
    $settings->add(new admin_setting_configselect('block_mylearning/show', new lang_string('show'),
                       new lang_string('settingsdescription', 'block_mylearning', new lang_string('show')),
                       'all', $optionsstatus));

    $optionssort = [
        'programname' => new lang_string('name'),
        'duedate' => new lang_string('conclusiondate', 'tool_program'),
        'lastaccess' => new lang_string('lastaccessed', 'tool_program'),
    ];
    $settings->add(new admin_setting_configselect('block_mylearning/sort', new lang_string('sortby'),
                       new lang_string('settingsdescription', 'block_mylearning', new lang_string('sortby')),
                       'lastaccess', $optionssort));
    $optionsview = [
        'viewcards' => new lang_string('expanded', 'tool_program'),
        'viewlist' => new lang_string('collapsed', 'tool_program'),
    ];
    $settings->add(new admin_setting_configselect('block_mylearning/display', new lang_string('display', 'tool_program'),
                       new lang_string('settingsdescription', 'block_mylearning', new lang_string('display', 'tool_program')),
                       'viewcards', $optionsview));
}
