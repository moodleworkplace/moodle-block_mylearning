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
 * My Learning block overview module.
 *
 * @module     block_mylearning/view
 * @copyright  2018 Moodle Pty Ltd <support@moodle.com>
 * @author     2018 David Matamoros <davidmc@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

define(['jquery', 'core/ajax', 'core/notification'], function($, ajax, Notification) {

    var SELECTOR = {
        ALL: '.dashboard-item',
        CERTIFICATIONNAME: '[data-region="certificationname"]',
        CONTROLREGION: '[data-region="dashboard-controls"]',
        COMPLETED: '.dashboard-item[data-completed="1"]',
        COURSES: '.dashboard-item[data-courses="1"]',
        COURSENAME: '[data-region="course-call-to-action"]',
        NOTCOMPLETED: '.dashboard-item:not([data-completed="1"])',
        PROGRAMS: '.dashboard-item[data-programs="1"]',
        MYLEARNINGDISPLAYDROPDOWN: '#mylearning-display-dropdown',
        MYLEARNINGDISPLAYDROPDOWNITEM: '.mylearning-display .dropdown-item',
        LEARNINGITEMNAME: '[data-region="mylearningname"]',
        MYLEARNINGSORTINGSDROPDOWN: '#mylearning-sorting-dropdown',
        MYLEARNINGSORTINGSDROPDOWNITEM: '.mylearning-sorting .dropdown-item',
        MYLEARNINGREGION: '[data-region="mylearning-overview-view"]',
        MYLEARNINGSTATUSDROPDOWN: '#mylearning-status-filter-dropdown',
        MYLEARNINGSTATUSDROPDOWNITEM: '.mylearning-status-filter .dropdown-item',
        MYLEARNINGSEARCHINPUT: '[data-region="mylearning-search-input"]',
        ENROLTOCOURSE: '.enrol_to_course',
    };

    var MYLEARNINGFILTER = {
        SHOW: 'filter-visible',
        HIDE: 'filter-hidden'
    };

    var SEARCHFILTER = {
        SHOW: 'search-visible',
        HIDE: 'search-hidden'
    };

    /**
     * Enrol a user into a course.
     *
     * @param {Number} courseid
     * @param {Number} programid
     */
    var enrolUserToCourse = function(courseid, programid) {
        var promises = ajax.call([
            {methodname: 'tool_program_enrol_user_to_course', args: {courseid: courseid, programid: programid}}
        ]);
        promises[0].done(function(response) {
            if (response.status && response.redirecturl) {
                window.location.href = response.redirecturl;
            }
        }).fail(Notification.exception);
    };

    /**
     * Filter for programs.
     *
     * @param {Element} region
     * @param {String} selector
     * @param {String} visibility
     */
    var filterLearningItems = function(region, selector, visibility) {
        let elements = region.find(selector);
        if (elements.length === 0) {
            $('.nothingtodisplay').show();
        } else {
            $('.nothingtodisplay').hide();
            elements.each(function() {
                $(this).removeClass(MYLEARNINGFILTER.SHOW).removeClass(MYLEARNINGFILTER.HIDE);
                $(this).addClass(visibility);
            });
        }
    };

    /**
     * Update user preferences.
     *
     * @param {String} type
     * @param {String} value
     */
    var updateUserPreferences = function(type, value) {
        var request = {
            methodname: 'core_user_update_user_preferences',
            args: {
                preferences: [{
                    type: type,
                    value: value,
                }]
            }
        };

        ajax.call([request])[0]
            .fail(Notification.exception);
    };

    /**
     * Show/Hide 'Nothing to display' content.
     *
     * @param {String} regionSelector
     */
    var checkNothingToDisplay = function(regionSelector) {
        const learningRegion = $(regionSelector + ' ' + SELECTOR.MYLEARNINGREGION);
        let visibleElements = learningRegion.find(SELECTOR.ALL + ':visible');
        if (visibleElements.length === 0) {
            learningRegion.find('.nothingtodisplay').show();
        } else {
            learningRegion.find('.nothingtodisplay').hide();
        }
    };

    /**
     * Show programs matching a status
     *
     * @param {String} regionSelector
     * @param {String} programfilter
     */
    var showLearningItemsWithStatus = function(regionSelector, programfilter) {
        M.util.js_pending('block_mylearning_filterstatus'); // Tell Behat to wait.
        const learningRegion = $(regionSelector + ' ' + SELECTOR.MYLEARNINGREGION);
        learningRegion.hide();
        // We hide all programs and show the selected ones.
        filterLearningItems(learningRegion, SELECTOR.ALL, MYLEARNINGFILTER.HIDE);
        filterLearningItems(learningRegion, SELECTOR[programfilter.toUpperCase()], MYLEARNINGFILTER.SHOW);
        updateUserPreferences('block_mylearning_status_filter', programfilter);
        learningRegion.fadeIn('fast', function() {
            M.util.js_complete('block_mylearning_filterstatus');
        });
    };

    /**
     * Update mylearning block display
     *
     * @param {String} regionSelector
     * @param {String} displaytype
     */
    var updateMylearningDisplay = function(regionSelector, displaytype) {
        const learningRegion = $(regionSelector + ' ' + SELECTOR.MYLEARNINGREGION);
        learningRegion.removeClass('viewcards').removeClass('viewlist').addClass(displaytype);
        updateUserPreferences('block_mylearning_view_filter', displaytype);
    };

    /**
     * Update program sorting.
     *
     * @param {String} regionSelector
     * @param {String} sortorder
     */
    var updateMylearningSorting = function(regionSelector, sortorder) {
        const learningRegion = $(regionSelector + ' ' + SELECTOR.MYLEARNINGREGION);
        let programs = learningRegion.find(SELECTOR.ALL);
        // Sort by element name.
        if (sortorder === 'mylearningname') {
            programs.sort((a, b) => {
                let mylearningNameA = $(a).find(SELECTOR.LEARNINGITEMNAME).text();
                let mylearningNameB = $(b).find(SELECTOR.LEARNINGITEMNAME).text();
                return mylearningNameA.localeCompare(mylearningNameB);
            });
            // Sort by element near conclusion date.
        } else if (sortorder === 'duedate') {
            programs.sort((a, b) => {
                return $(a).data("lowestduedate") - $(b).data("lowestduedate");
            });
            // Sort by element last access date.
        } else if (sortorder === 'lastaccess') {
            programs.sort((a, b) => {
                // If lastaccess is the same order by name to be consistent with the app.
                if ($(b).data("lastaccess") === $(a).data("lastaccess")) {
                    const compareA = $(a).find(SELECTOR.LEARNINGITEMNAME).text().toLowerCase(),
                        compareB = $(b).find(SELECTOR.LEARNINGITEMNAME).text().toLowerCase();
                    return compareA.localeCompare(compareB);
                }
                return $(b).data("lastaccess") - $(a).data("lastaccess");
            });
        }
        // Update the elements list.
        learningRegion.remove(SELECTOR.ALL).prepend(programs);
        // Update the user preferences.
        updateUserPreferences('block_mylearning_sort_filter', sortorder);
        // Add a little visual affect to show something is happening.
        learningRegion.hide().fadeIn('fast');
    };

    /**
     * Show matching programs.
     *
     * @param {String} regionSelector
     * @param {String} query string
     */
    var showMatchingLearningItems = function(regionSelector, query) {
        const learningRegion = $(regionSelector + ' ' + SELECTOR.MYLEARNINGREGION);
        learningRegion.find(SELECTOR.ALL).filter(function() {
            let learningItem = $(this);
            let dashboardText = learningItem.find(SELECTOR.LEARNINGITEMNAME).text();
            dashboardText += ' ' + learningItem.find(SELECTOR.COURSENAME).text();
            dashboardText += ' ' + learningItem.find(SELECTOR.CERTIFICATIONNAME).text();
            if (dashboardText.toLowerCase().indexOf(query) > -1) {
                learningItem.removeClass(SEARCHFILTER.HIDE).addClass(SEARCHFILTER.SHOW);
            } else {
                learningItem.removeClass(SEARCHFILTER.SHOW).addClass(SEARCHFILTER.HIDE);
            }
            return true;
        });
    };

    return {
        /**
         * Initialises mylearning overview.
         *
         * @param {string} regionSelector
         */
        init: function(regionSelector) {
            const learningRegion = $(regionSelector);

            // Maincontent anchor hack to mantain accesibility.
            $("#maincontent").detach().prependTo('#region-main');

            // Dispatch resize event to correctly recalculate visible courses for recently accessed courses block.
            $('body').on('shown shown.bs.tab', function() {
                window.dispatchEvent(new Event('resize'));
            });

            // Load correct filter saved in user preferences.
            if ($(SELECTOR.MYLEARNINGREGION).data('showfilters') === 1) {
                let statusValue = $(regionSelector + ' ' + SELECTOR.MYLEARNINGSTATUSDROPDOWN).data('selected');
                let displayValue = $(regionSelector + ' ' + SELECTOR.MYLEARNINGDISPLAYDROPDOWN).data('selected');
                let sortingValue = $(regionSelector + ' ' + SELECTOR.MYLEARNINGSORTINGSDROPDOWN).data('selected');
                showLearningItemsWithStatus(regionSelector, statusValue);
                checkNothingToDisplay(regionSelector);
                updateMylearningDisplay(regionSelector, displayValue);
                updateMylearningSorting(regionSelector, sortingValue);
            }

            $(".section_expand").click(function(e) {
                e.preventDefault();
                $(this).next('.content').toggle();
                $(this).find('[class$="-icon-container"]').toggle();
            });

            learningRegion
                .on('click', SELECTOR.ENROLTOCOURSE, function(e) {
                    e.preventDefault();
                    enrolUserToCourse($(this).data('courseid'), $(this).data('programid'));
                })
                // Event listener for statusses.
                .on('click', SELECTOR.MYLEARNINGSTATUSDROPDOWNITEM, function(e) {
                    e.preventDefault();
                    showLearningItemsWithStatus(regionSelector, $(this).data('value'));
                    checkNothingToDisplay(regionSelector);
                })
                // Event listener for the view type.
                .on('click', SELECTOR.MYLEARNINGDISPLAYDROPDOWNITEM, function(e) {
                    e.preventDefault();
                    updateMylearningDisplay(regionSelector, $(this).data('value'));
                })
                // Event listener for sorting learning items.
                .on('click', SELECTOR.MYLEARNINGSORTINGSDROPDOWNITEM, function(e) {
                    e.preventDefault();
                    updateMylearningSorting(regionSelector, $(this).data('value'));
                })
                .on('keyup', SELECTOR.MYLEARNINGSEARCHINPUT, function() {
                    var query = $(this).val().toLowerCase().trim();
                    showMatchingLearningItems(regionSelector, query);
                    checkNothingToDisplay(regionSelector);
                });
        }
    };
});
