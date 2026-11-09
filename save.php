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

// Keep a colour only if its checkbox is ticked and the value is a valid hex colour.
$hex = '/^#[0-9a-f]{6}$/i';
$section = optional_param('section', '', PARAM_TEXT);
$activity = optional_param('activity', '', PARAM_TEXT);
$usesection = optional_param('use_section', 0, PARAM_INT);
$useactivity = optional_param('use_activity', 0, PARAM_INT);

$config = $block->config ?? new stdClass();
$config->section = ($usesection && preg_match($hex, $section)) ? strtolower($section) : null;
$config->activity = ($useactivity && preg_match($hex, $activity)) ? strtolower($activity) : null;

$block->config = $config;
$block->instance_config_commit();

redirect(new moodle_url('/course/view.php', ['id' => $courseid]), get_string('changessaved'));
