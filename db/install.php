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
 * block_mylearning installation script
 *
 * @package   block_mylearning
 * @copyright 2021 Moodle Pty Ltd <support@moodle.com>
 * @author    2021 Mikel Martín <mikel@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

/**
 * Custom code to be run on installing the plugin.
 *
 * @return bool
 */
function xmldb_block_mylearning_install() {
    global $DB;

    if (!defined('BEHAT_SITE_RUNNING') && !(defined('PHPUNIT_TEST') && PHPUNIT_TEST)) {
        // During initial installation,
        // during upgrade from LMS to Workplace,
        // when installing the block on the Workplace site that has dashboardlearning=1 -
        // hide the block by default because it will duplicate the learning tab.
        // (If the setting dashboardlearning does not exist, this means that we are upgrading to workplace
        // and it will be set to 1 in the end of the upgrade).
        if (during_initial_install() || !($themeconfig = get_config('theme_workplace')) ||
                !isset($themeconfig->dashboardlearning) || !empty($themeconfig->dashboardlearning)) {
            $DB->set_field('block', 'visible', '0', ['name' => 'mylearning']);
            add_to_config_log('block_visibility', 1, '0', 'mylearning');
        }
    }

    return true;
}
