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
 * CompassFont block: font type, font size and text colour for learners.
 *
 * Admins and managers (sectioncolour capability) also see course colour controls
 * for section and activity font colours. Saving them stores the colours on this
 * block instance, so everyone on the course sees them and only admins can change them.
 *
 * @package    block_compassfont
 * @copyright  2026 Michael Williams
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * CompassFont block class.
 *
 * @package    block_compassfont
 * @copyright  2026 Michael Williams
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_compassfont extends block_base {
    /** @var string Default text colour, used when the learner has not picked one. */
    const DEFAULT_TEXT_COLOUR = '#000000';

    /** @var string Default colour shown in the course colour pickers. */
    const DEFAULT_COURSE_COLOUR = '#000000';

    /**
     * Allowed font families. The key is stored; 'css' is the font stack (null = site default).
     *
     * @return array
     */
    public static function font_families(): array {
        return [
            'default'  => ['label' => get_string('default', 'block_compassfont'), 'css' => null],
            'sans'     => ['label' => get_string('family_sans', 'block_compassfont'),
                           'css' => 'Arial, Helvetica, sans-serif'],
            'serif'    => ['label' => get_string('family_serif', 'block_compassfont'),
                           'css' => 'Georgia, \'Times New Roman\', serif'],
            'mono'     => ['label' => get_string('family_mono', 'block_compassfont'),
                           'css' => '\'Courier New\', Courier, monospace'],
            'readable' => ['label' => get_string('family_readable', 'block_compassfont'),
                           'css' => 'Verdana, Geneva, sans-serif'],
        ];
    }

    /**
     * Allowed font sizes (CSS values).
     *
     * @return array
     */
    public static function font_sizes(): array {
        return ['14px', '16px', '18px', '20px', '22px', '24px'];
    }

    /**
     * Build one course colour picker with a checkbox to turn it on or off.
     *
     * @param string $name 'section' or 'activity'.
     * @param string $label Visible label.
     * @param string|null $current Saved hex colour, or null if not set.
     * @return string
     */
    private static function course_colour_field(string $name, string $label, ?string $current): string {
        $on = !empty($current);
        $id = 'compassfont-' . $name;
        $value = $on ? $current : self::DEFAULT_COURSE_COLOUR;

        $pickerattrs = [
            'type' => 'color',
            'id' => $id,
            'name' => $name,
            'value' => $value,
            'class' => 'form-control form-control-color w-100 mb-1',
        ];
        $html = html_writer::tag('label', $label, ['for' => $id]);
        $html .= html_writer::empty_tag('input', $pickerattrs);

        $checkbox = [
            'type' => 'checkbox',
            'name' => 'use_' . $name,
            'value' => 1,
            'aria-label' => get_string('usecolour', 'block_compassfont') . ' (' . $label . ')',
        ];
        if ($on) {
            $checkbox['checked'] = 'checked';
        }
        $tick = html_writer::empty_tag('input', $checkbox) . ' ' . get_string('usecolour', 'block_compassfont');
        $html .= html_writer::tag('label', $tick, ['class' => 'mb-2 d-block']);

        return $html;
    }

    /**
     * Set the block title.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_compassfont');
    }

    /**
     * Course pages only: the block is not offered on the Dashboard.
     *
     * @return array
     */
    public function applicable_formats() {
        return ['course-view' => true];
    }

    /**
     * Only one CompassFont block per course.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Course colours are set from the block body, not the settings dialog.
     *
     * @return bool
     */
    public function has_config() {
        return false;
    }

    /**
     * Build the block content and pass the settings to the page script.
     *
     * @return stdClass
     */
    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->footer = '';

        // Only admins, managers and course creators see the course colour controls.
        $cancoursecolours = has_capability('block/compassfont:sectioncolour', $this->context);

        // Font type options.
        $familyopts = [];
        $fontcss = [];
        foreach (self::font_families() as $key => $font) {
            $familyopts[$key] = $font['label'];
            $fontcss[$key] = $font['css'];
        }

        // Font size options.
        $sizeopts = ['default' => get_string('default', 'block_compassfont')];
        foreach (self::font_sizes() as $size) {
            $sizeopts[$size] = $size;
        }

        // Learners' own controls.
        $html = html_writer::start_div('block_compassfont_controls');

        $html .= html_writer::tag('label', get_string('fonttype', 'block_compassfont'), ['for' => 'compassfont-family']);
        $familyattrs = ['id' => 'compassfont-family', 'class' => 'custom-select custom-select-sm mb-2'];
        $html .= html_writer::select($familyopts, 'compassfont-family', 'default', false, $familyattrs);

        $html .= html_writer::tag('label', get_string('fontsize', 'block_compassfont'), ['for' => 'compassfont-size']);
        $sizeattrs = ['id' => 'compassfont-size', 'class' => 'custom-select custom-select-sm mb-2'];
        $html .= html_writer::select($sizeopts, 'compassfont-size', 'default', false, $sizeattrs);

        $html .= html_writer::tag('label', get_string('textcolour', 'block_compassfont'), ['for' => 'compassfont-colour']);
        $colourattrs = [
            'type' => 'color',
            'id' => 'compassfont-colour',
            'name' => 'compassfont-colour',
            'value' => self::DEFAULT_TEXT_COLOUR,
            'class' => 'form-control form-control-color w-100 mb-1',
            'title' => get_string('textcolour', 'block_compassfont'),
        ];
        $html .= html_writer::empty_tag('input', $colourattrs);

        $usecolourlabel = get_string('usecolour', 'block_compassfont') . ' (' .
            get_string('textcolour', 'block_compassfont') . ')';
        $usecolour = [
            'type' => 'checkbox',
            'id' => 'compassfont-use-colour',
            'value' => 1,
            'aria-label' => $usecolourlabel,
        ];
        $tick = html_writer::empty_tag('input', $usecolour) . ' ' . get_string('usecolour', 'block_compassfont');
        $html .= html_writer::tag('label', $tick, ['class' => 'mb-2 d-block', 'for' => 'compassfont-use-colour']);

        // Course colour controls: admins, managers and course creators only.
        if ($cancoursecolours) {
            $saved = $this->config ?? new stdClass();
            $savedsection = $saved->section ?? null;
            $savedactivity = $saved->activity ?? null;

            $action = (new moodle_url('/blocks/compassfont/save.php'))->out(false);
            $formattrs = ['method' => 'post', 'action' => $action, 'class' => 'block_compassfont_course_colours'];
            $html .= html_writer::start_tag('form', $formattrs);

            $instanceattrs = ['type' => 'hidden', 'name' => 'instanceid', 'value' => $this->instance->id];
            $html .= html_writer::empty_tag('input', $instanceattrs);
            $sesskeyattrs = ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()];
            $html .= html_writer::empty_tag('input', $sesskeyattrs);

            $sectionlabel = get_string('sectioncolour', 'block_compassfont');
            $html .= self::course_colour_field('section', $sectionlabel, $savedsection);
            $activitylabel = get_string('activitycolour', 'block_compassfont');
            $html .= self::course_colour_field('activity', $activitylabel, $savedactivity);

            $submitattrs = [
                'type' => 'submit',
                'value' => get_string('savecolours', 'block_compassfont'),
                'class' => 'btn btn-primary btn-sm btn-block',
            ];
            $html .= html_writer::empty_tag('input', $submitattrs);
            $html .= html_writer::end_tag('form');
        }

        // Reset sits at the bottom and clears the learner's own font settings.
        $resetattrs = ['type' => 'button', 'id' => 'compassfont-reset', 'class' => 'btn btn-secondary btn-sm btn-block mt-2'];
        $html .= html_writer::tag('button', get_string('reset', 'block_compassfont'), $resetattrs);

        $html .= html_writer::end_div();

        $this->content->text = $cancoursecolours ? $html : '';

        // The course's section and activity colours, saved on this block instance.
        $section = $this->config->section ?? null;
        $activity = $this->config->activity ?? null;

        $options = [
            'storageKey' => 'block_compassfont',
            'fonts' => $fontcss,
            'sizes' => self::font_sizes(),
            'textDefault' => self::DEFAULT_TEXT_COLOUR,
            'section' => $section,
            'activity' => $activity,
            'canEdit' => $cancoursecolours,
        ];
        $this->page->requires->js_call_amd('block_compassfont/compassfont', 'init', [$options]);

        return $this->content;
    }
}
