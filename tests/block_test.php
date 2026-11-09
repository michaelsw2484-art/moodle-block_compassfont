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
 * Tests for the CompassFont block.
 *
 * @package    block_compassfont
 * @copyright  2026 Michael Williams
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class block_test extends \advanced_testcase {
    /**
     * Load the block class through Moodle's block library.
     */
    private function load_block(): void {
        global $CFG;
        require_once($CFG->libdir . '/blocklib.php');
        block_instance('compassfont');
    }

    /**
     * The font families include the site default and a CSS stack for each other choice.
     */
    public function test_font_families(): void {
        $this->load_block();
        $families = \block_compassfont::font_families();
        $this->assertArrayHasKey('default', $families);
        $this->assertNull($families['default']['css']);
        foreach (['sans', 'serif', 'mono', 'readable'] as $key) {
            $this->assertArrayHasKey($key, $families);
            $this->assertNotEmpty($families[$key]['css']);
            $this->assertNotEmpty($families[$key]['label']);
        }
    }

    /**
     * The font sizes are all pixel values.
     */
    public function test_font_sizes(): void {
        $this->load_block();
        $sizes = \block_compassfont::font_sizes();
        $this->assertNotEmpty($sizes);
        foreach ($sizes as $size) {
            $this->assertMatchesRegularExpression('/^\d+px$/', $size);
        }
    }

    /**
     * Only people who can set course colours see the block, and learners are not given that permission.
     */
    public function test_capabilities(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $manager = $this->getDataGenerator()->create_user();
        $managerrole = $DB->get_field('role', 'id', ['shortname' => 'manager']);
        role_assign($managerrole, $manager->id, \context_system::instance()->id);

        $this->assertFalse(has_capability('block/compassfont:sectioncolour', $context, $student));
        $this->assertFalse(has_capability('block/compassfont:sectioncolour', $context, $teacher));
        $this->assertTrue(has_capability('block/compassfont:sectioncolour', $context, $manager));
        $this->assertTrue(has_capability('block/compassfont:addinstance', $context, $teacher));
        $this->assertFalse(has_capability('block/compassfont:addinstance', $context, $student));
    }

    /**
     * Only course pages can hold the block, and only one per course.
     */
    public function test_block_settings(): void {
        $this->load_block();
        $block = new \block_compassfont();
        $this->assertEquals(['course-view' => true], $block->applicable_formats());
        $this->assertFalse($block->instance_allow_multiple());
    }
}
