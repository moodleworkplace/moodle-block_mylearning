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
 * block_mylearning steps definitions.
 *
 * @package    block_mylearning
 * @category   test
 * @copyright  2022 Moodle Pty Ltd <support@moodle.com>
 * @author     2022 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Steps definitions for block_mylearning.
 *
 * @package    block_mylearning
 * @category   test
 * @copyright  2022 Moodle Pty Ltd <support@moodle.com>
 * @author     2022 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class behat_block_mylearning extends behat_base {

    /**
     * Allocates users to programs
     *
     * @Given /^default dashboard does not have any blocks except for My learning in "(?P<region_string>(?:[^"]|\\")*)" region$/
     *
     * @param string $region block region, for example 'content', 'side-pre', 'side-post'
     */
    public function default_dashboard_does_not_have_any_blocks_except_for_my_learning($region): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/my/lib.php');
        $page = my_get_page(null, MY_PAGE_PRIVATE);
        if ($blocks = $DB->get_records('block_instances', array('parentcontextid' => context_system::instance()->id,
            'pagetypepattern' => 'my-index'))) {
            foreach ($blocks as $block) {
                if (is_null($block->subpagepattern) || $block->subpagepattern == $page->id) {
                    blocks_delete_instance($block);
                }
            }
        }

        $blockinstance = new stdClass;
        $blockinstance->blockname = 'mylearning';
        $blockinstance->parentcontextid = context_system::instance()->id;
        $blockinstance->showinsubcontexts = true;
        $blockinstance->pagetypepattern = 'my-index';
        $blockinstance->subpagepattern = $page->id;
        $blockinstance->defaultregion = $region;
        $blockinstance->defaultweight = 0;
        $blockinstance->configdata = '';
        $blockinstance->timecreated = time();
        $blockinstance->timemodified = $blockinstance->timecreated;
        $blockinstance->id = $DB->insert_record('block_instances', $blockinstance);

        // Ensure the block context is created.
        context_block::instance($blockinstance->id);

        // If the new instance was created, allow it to do additional setup.
        if ($block = block_instance('mylearning', $blockinstance)) {
            $block->instance_create();
        }
    }

    /**
     * Return the list of partial named selectors.
     *
     * Those selectors can be used to capture dashboard elements. Examples:
     *    And I click on "Expand" "link" in the "ProgramName" "tool_program > Dashboard item"
     *
     * @return array
     */
    public static function get_partial_named_selectors(): array {
        return [
            new behat_component_named_selector('Dashboard item', [
                <<<XPATH
    .//div[contains(concat(' ', normalize-space(@class), ' '), ' dashboard-item ')
            and
            normalize-space(descendant::*[contains(concat(' ', normalize-space(@class), ' '), ' element-name ')]) = %locator%
            ]
XPATH
            ], true),
        ];
    }
}
