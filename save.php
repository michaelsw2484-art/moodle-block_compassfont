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

/**
 * Saves the course section and activity font colours for a CompassFont block.
 *
 * @package    block_compassfont
 * @copyright  2026 Michael Williams
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/blocklib.php');

require_sesskey();

if (!data_submitted()) {
    throw new moodle_exception('invalidrequest');
}

$instanceid = required_param('instanceid', PARAM_INT);

$instancerecord = $DB->get_record('block_instances', ['id' => $instanceid, 'blockname' => 'compassfont'], '*', MUST_EXIST);
$block = block_instance('compassfont', $instancerecord);
$blockcontext = $block->context;

$coursecontext = $blockcontext->get_course_context(false);
$courseid = $coursecontext ? $coursecontext->instanceid : SITEID;

require_login($courseid);
require_capability('block/compassfont:sectioncolour', $blockcontext);

// Read the form. Colours are kept only while their "Use this colour" tick is on.
$submitted = (object) [
    'family' => optional_param('family', '', PARAM_ALPHA),
    'size' => optional_param('size', '', PARAM_ALPHANUMEXT),
];
foreach (['colour', 'section', 'activity'] as $name) {
    $submitted->$name = optional_param('use_' . $name, 0, PARAM_BOOL) ? optional_param($name, '', PARAM_TEXT) : null;
}

// Keep only values on the allow-lists (font types, font sizes, hex colours).
$config = $block->config ?? new stdClass();
foreach (block_compassfont::clean_settings($submitted) as $name => $value) {
    $config->$name = $value;
}

$block->config = $config;
$block->instance_config_commit();

redirect(
    new moodle_url('/course/view.php', ['id' => $courseid]),
    get_string('changessaved'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
