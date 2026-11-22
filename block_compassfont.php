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
     * The saved course settings, cleaned: only values on the allow-lists are kept.
     *
     * @param stdClass|null $config Block instance configuration.
     * @return array family (key or null), size (CSS value or null), colour, section and activity (hex or null).
     */
    public static function clean_settings(?stdClass $config): array {
        $config = $config ?? new stdClass();
        $hex = '/^#[0-9a-f]{6}$/i';
        $families = self::font_families();
        $out = [];
        $family = $config->family ?? null;
        $out['family'] = (is_string($family) && isset($families[$family]) && $families[$family]['css'] !== null)
            ? $family : null;
        $size = $config->size ?? null;
        $out['size'] = (is_string($size) && in_array($size, self::font_sizes(), true)) ? $size : null;
        foreach (['colour', 'section', 'activity'] as $name) {
            $value = $config->$name ?? null;
            $out[$name] = (is_string($value) && preg_match($hex, $value)) ? strtolower($value) : null;
        }
        return $out;
    }

    /**
     * Build one colour picker with a checkbox to turn it on or off.
     *
     * @param string $name 'colour', 'section' or 'activity'.
     * @param string $label Visible label.
     * @param string|null $current Saved hex colour, or null if not set.
     * @return string
     */
    private static function colour_field(string $name, string $label, ?string $current): string {
        $on = !empty($current);
        $id = 'compassfont-' . $name;
        $default = ($name === 'colour') ? self::DEFAULT_TEXT_COLOUR : self::DEFAULT_COURSE_COLOUR;

        $pickerattrs = [
            'type' => 'color',
            'id' => $id,
            'name' => $name,
            'value' => $on ? $current : $default,
            'class' => 'form-control form-control-color w-100 mb-1',
        ];
        $html = html_writer::tag('label', $label, ['for' => $id]);
        $html .= html_writer::empty_tag('input', $pickerattrs);

        $checkbox = [
            'type' => 'checkbox',
            'id' => $id . '-use',
            'name' => 'use_' . $name,
            'value' => 1,
            'aria-label' => get_string('usecolour', 'block_compassfont') . ' (' . $label . ')',
        ];
        if ($on) {
            $checkbox['checked'] = 'checked';
        }
        $tick = html_writer::empty_tag('input', $checkbox) . ' ' . get_string('usecolour', 'block_compassfont');
        $html .= html_writer::tag('label', $tick, ['class' => 'mb-2 d-block', 'for' => $id . '-use']);

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
     * Settings are changed from the block body, not the settings dialog.
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
        $this->content->text = '';

        $options = $this->page_options();
        if ($options['canEdit']) {
            $this->content->text = $this->controls();
        }
        if ($options['canEdit'] || self::has_settings($options)) {
            $this->page->requires->js_call_amd('block_compassfont/compassfont', 'init', [$options]);
        }

        return $this->content;
    }

    /**
     * The form with the course settings, for people with the permission.
     *
     * @return string
     */
    private function controls(): string {
        $saved = self::clean_settings($this->config ?? null);

        $familyopts = [];
        foreach (self::font_families() as $key => $font) {
            $familyopts[$key] = $font['label'];
        }
        $sizeopts = ['default' => get_string('default', 'block_compassfont')];
        foreach (self::font_sizes() as $size) {
            $sizeopts[$size] = $size;
        }

        $action = (new moodle_url('/blocks/compassfont/save.php'))->out(false);
        $html = html_writer::start_div('block_compassfont_controls');
        $html .= html_writer::tag('p', get_string('appliestoeveryone', 'block_compassfont'), ['class' => 'small text-muted mb-2']);
        $html .= html_writer::start_tag('form', ['method' => 'post', 'action' => $action, 'id' => 'compassfont-form']);
        $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'instanceid', 'value' => $this->instance->id]);
        $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

        $html .= html_writer::tag('label', get_string('fonttype', 'block_compassfont'), ['for' => 'compassfont-family']);
        $html .= html_writer::select($familyopts, 'family', $saved['family'] ?? 'default', false,
            ['id' => 'compassfont-family', 'class' => 'custom-select custom-select-sm mb-2']);

        $html .= html_writer::tag('label', get_string('fontsize', 'block_compassfont'), ['for' => 'compassfont-size']);
        $html .= html_writer::select($sizeopts, 'size', $saved['size'] ?? 'default', false,
            ['id' => 'compassfont-size', 'class' => 'custom-select custom-select-sm mb-2']);

        $html .= self::colour_field('colour', get_string('textcolour', 'block_compassfont'), $saved['colour']);
        $html .= self::colour_field('section', get_string('sectioncolour', 'block_compassfont'), $saved['section']);
        $html .= self::colour_field('activity', get_string('activitycolour', 'block_compassfont'), $saved['activity']);

        $html .= html_writer::empty_tag('input', [
            'type' => 'submit',
            'value' => get_string('savesettings', 'block_compassfont'),
            'class' => 'btn btn-primary btn-sm btn-block',
        ]);
        $html .= html_writer::tag('button', get_string('reset', 'block_compassfont'),
            ['type' => 'button', 'id' => 'compassfont-reset', 'class' => 'btn btn-secondary btn-sm btn-block mt-2']);
        $html .= html_writer::end_tag('form');
        $html .= html_writer::end_div();

        return $html;
    }

    /**
     * Whether any course setting is saved, so the script has something to apply.
     *
     * @param array $options From page_options().
     * @return bool
     */
    public static function has_settings(array $options): bool {
        return (bool) ($options['family'] || $options['size'] || $options['colour'] ||
            $options['section'] || $options['activity']);
    }

    /**
     * Settings passed to compassfont.js, on the course page and on the course's other pages.
     *
     * @return array
     */
    public function page_options(): array {
        $saved = self::clean_settings($this->config ?? null);
        $fonts = self::font_families();
        $fontcss = [];
        foreach ($fonts as $key => $font) {
            $fontcss[$key] = $font['css'];
        }

        return [
            'fonts' => $fontcss,
            'sizes' => self::font_sizes(),
            'textDefault' => self::DEFAULT_TEXT_COLOUR,
            'family' => $saved['family'] ? $fonts[$saved['family']]['css'] : null,
            'size' => $saved['size'],
            'colour' => $saved['colour'],
            'section' => $saved['section'],
            'activity' => $saved['activity'],
            'canEdit' => has_capability('block/compassfont:sectioncolour', $this->context),
        ];
    }
}
