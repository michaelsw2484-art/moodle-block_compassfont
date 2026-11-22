<?php
// This file is part of Moodle - http://moodle.org/
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

namespace block_compassfont;

/**
 * Hook callbacks for the CompassFont block.
 *
 * @package    block_compassfont
 * @copyright  2026 Michael Williams
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Apply CompassFont inside activities and on the other pages of a course.
     *
     * The block only shows on the course page. On the other pages of a course that has a
     * CompassFont block with saved settings, this callback loads the same script, so everyone
     * on the course sees the course's font type, font size, text colour, and section and
     * activity colours inside activities too.
     *
     * @param \core\hook\output\before_footer_html_generation $hook
     */
    public static function before_footer(\core\hook\output\before_footer_html_generation $hook): void {
        global $PAGE, $DB, $CFG;

        if (during_initial_install() || !isloggedin() || isguestuser()) {
            return;
        }
        // The block itself handles the course page.
        if (strpos($PAGE->pagetype, 'course-view') === 0) {
            return;
        }
        $coursecontext = $PAGE->context->get_course_context(false);
        if (!$coursecontext || $coursecontext->contextlevel != CONTEXT_COURSE || $coursecontext->instanceid == SITEID) {
            return;
        }
        $record = $DB->get_record('block_instances', [
            'blockname' => 'compassfont',
            'parentcontextid' => $coursecontext->id,
        ], '*', IGNORE_MULTIPLE);
        if (!$record) {
            return;
        }
        require_once($CFG->libdir . '/blocklib.php');
        $block = block_instance('compassfont', $record);
        if (!$block) {
            return;
        }
        $options = $block->page_options();
        // Nothing to do when the course has no settings saved.
        if (!\block_compassfont::has_settings($options)) {
            return;
        }
        $PAGE->requires->js_call_amd('block_compassfont/compassfont', 'applyOutside', [$options]);
    }
}
