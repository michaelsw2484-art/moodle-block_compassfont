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
        $nonediting = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $this->assertTrue(has_capability('block/compassfont:sectioncolour', $context, $teacher));
        $this->assertFalse(has_capability('block/compassfont:sectioncolour', $context, $nonediting));
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

    /**
     * The hook callback loads the script on an activity page for everyone on the course,
     * once the course's CompassFont block has settings saved.
     */
    public function test_hook_applies_settings_in_activities(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $coursecontext = \context_course::instance($course->id);
        $manager = $this->getDataGenerator()->create_user();
        $managerrole = $DB->get_field('role', 'id', ['shortname' => 'manager']);
        role_assign($managerrole, $manager->id, \context_system::instance()->id);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $cm = get_coursemodule_from_instance('page', $page->id);
        $renderer = (new \moodle_page())->get_renderer('core');
        $hook = new \core\hook\output\before_footer_html_generation($renderer);

        $run = function (\stdClass $user) use ($cm, $hook): int {
            $this->setUser($user);
            $GLOBALS['PAGE'] = new \moodle_page();
            $GLOBALS['PAGE']->set_cm($cm);
            $GLOBALS['PAGE']->set_url('/mod/page/view.php', ['id' => $cm->id]);
            hook_callbacks::before_footer($hook);
            return strpos($GLOBALS['PAGE']->requires->get_end_code(), 'applyOutside') === false ? 0 : 1;
        };

        // No block in the course: nothing is loaded, for anyone.
        $this->assertSame(0, $run($manager));

        // With the block in the course but nothing saved: nothing is loaded.
        $blockinstance = (object) [
            'blockname' => 'compassfont',
            'parentcontextid' => $coursecontext->id,
            'showinsubcontexts' => 0,
            'pagetypepattern' => 'course-view-*',
            'defaultregion' => 'side-pre',
            'defaultweight' => 0,
            'configdata' => '',
            'timecreated' => time(),
            'timemodified' => time(),
        ];
        $blockinstance->id = $DB->insert_record('block_instances', $blockinstance);
        \context_block::instance($blockinstance->id);
        $this->assertSame(0, $run($student));
        $this->assertSame(0, $run($manager));

        // Once settings are saved, everyone on the course gets them inside activities too.
        $config = (object) ['family' => 'serif', 'size' => '18px', 'colour' => '#123456'];
        $DB->set_field('block_instances', 'configdata', base64_encode(serialize($config)), ['id' => $blockinstance->id]);
        $this->assertSame(1, $run($student));
        $this->assertSame(1, $run($manager));
    }

    /**
     * The settings passed to the script keep only valid colours.
     */
    public function test_page_options(): void {
        global $DB;
        $this->resetAfterTest();
        $this->load_block();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $record = (object) [
            'blockname' => 'compassfont',
            'parentcontextid' => \context_course::instance($course->id)->id,
            'showinsubcontexts' => 0,
            'pagetypepattern' => 'course-view-*',
            'defaultregion' => 'side-pre',
            'defaultweight' => 0,
            'configdata' => base64_encode(serialize((object) [
                'family' => 'serif', 'size' => '99px', 'colour' => '#ABCDEF', 'section' => '#AA0000', 'activity' => 'red',
            ])),
            'timecreated' => time(),
            'timemodified' => time(),
        ];
        $record->id = $DB->insert_record('block_instances', $record);
        \context_block::instance($record->id);

        $options = block_instance('compassfont', $record)->page_options();
        $this->assertSame(\block_compassfont::font_families()['serif']['css'], $options['family']);
        $this->assertNull($options['size']);
        $this->assertSame('#abcdef', $options['colour']);
        $this->assertSame('#aa0000', $options['section']);
        $this->assertNull($options['activity']);
        $this->assertTrue($options['canEdit']);
        $this->assertSame(\block_compassfont::font_sizes(), $options['sizes']);
        $this->assertTrue(\block_compassfont::has_settings($options));
    }

    /**
     * Only allowed values are kept when settings are saved.
     */
    public function test_clean_settings(): void {
        $this->load_block();
        $clean = \block_compassfont::clean_settings((object) [
            'family' => 'default', 'size' => '18px', 'colour' => 'javascript:x', 'section' => '#00FF00',
        ]);
        $this->assertSame([
            'family' => null, 'size' => '18px', 'colour' => null, 'section' => '#00ff00', 'activity' => null,
        ], $clean);
        $this->assertSame(['family' => null, 'size' => null, 'colour' => null, 'section' => null, 'activity' => null],
            \block_compassfont::clean_settings(null));
    }
}
