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
 * Callbacks for plugin block_mylearning
 *
 * @package    block_mylearning
 * @copyright  2022 Moodle Pty Ltd <support@moodle.com>
 * @author     2022 Odei Alba <odei.alba@moodle.com>
 * @license    Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */

/**
 * Get icon mapping for font-awesome.
 */
function block_mylearning_get_fontawesome_icon_map() {
    return [
        'block_mylearning:t/circle' => 'fa-circle',
        'block_mylearning:check-circle-o' => 'fa-check-circle-o',
    ];
}

/**
 * Get the current user preferences that are available
 *
 * @return mixed Array representing current options along with defaults
 */
function block_mylearning_user_preferences() {
    $preferences['block_mylearning_status_filter'] = [
        'null' => NULL_NOT_ALLOWED,
        'default' => 'all',
        'type' => PARAM_ALPHA,
        'choices' => [
            'all',
            'completed',
            'notcompleted',
            'courses',
            'programs',
        ]
    ];

    $preferences['block_mylearning_view_filter'] = [
        'null' => NULL_NOT_ALLOWED,
        'default' => 'viewcards',
        'type' => PARAM_ALPHA,
        'choices' => [
            'viewcards',
            'viewlist'
        ]
    ];

    $preferences['block_mylearning_sort_filter'] = [
        'null' => NULL_NOT_ALLOWED,
        'default' => 'duedate',
        'type' => PARAM_ALPHA,
        'choices' => [
            'duedate',
            'mylearningname',
            'lastaccess'
        ]
    ];

    return $preferences;
}
