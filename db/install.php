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
// Moodle Workplace Code is dual-licensed under the terms of both the
// single GNU General Public Licence version 3.0, dated 29 June 2007
// and the terms of the proprietary Moodle Workplace Licence strictly
// controlled by Moodle Pty Ltd and its certified premium partners.
// Wherever conflicting terms exist, the terms of the MWL are binding
// and shall prevail.

/**
 * block_mylearning installation script
 *
 * @package   block_mylearning
 * @copyright 2021 Moodle Pty Ltd <support@moodle.com>
 * @author    2021 Mikel Martín <mikel@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @license   Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Custom code to be run on installing the plugin.
 *
 * @return bool
 */
function xmldb_block_mylearning_install() {
    global $DB;

    if (!defined('BEHAT_SITE_RUNNING') && !(defined('PHPUNIT_TEST') && PHPUNIT_TEST)) {
        if (!$block = $DB->get_record('block', ['name' => 'mylearning'], '*', MUST_EXIST)) {
            throw new moodle_exception('blockdoesnotexist', 'block_mylearning');
        }
        // Enable this block for the Moodle LMS sites that are upgraded to Moodle Workplace or if the Teams tab is disabled
        // in the theme settings.
        if (!during_initial_install() && empty(get_config('theme_workplace')->dashboardlearning)) {
            $DB->set_field('block', 'visible', '1', ['id' => $block->id]);
            add_to_config_log('block_visibility', $block->visible, '1', $block->name);
        } else {
            $DB->set_field('block', 'visible', '0', ['id' => $block->id]);
            add_to_config_log('block_visibility', $block->visible, '0', $block->name);
        }
        core_plugin_manager::reset_caches();
    }

    return true;
}