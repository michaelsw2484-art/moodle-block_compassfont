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
 * CompassFont: font type, font size and text colour for learners, plus the
 * course section and activity font colours.
 *
 * Learner preferences are kept in localStorage (per browser). Font size is applied
 * to the root element so rem-based text scales; the other settings are applied
 * using CSS custom properties defined in styles.css.
 *
 * Course colours come from the block instance (saved by an admin). Admins also get
 * a live preview: changing a course colour picker updates the page straight away,
 * and nothing is stored until they press "Save course colours".
 *
 * Section titles and activity names have their words wrapped in a span so only
 * the text is coloured, not the icons or edit pencils inside them.
 *
 * @module     block_compassfont/compassfont
 * @copyright  2026 Michael Williams
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    'use strict';

    var config = null;
    var HEX = /^#[0-9a-f]{6}$/i;
    var TITLE_CLASS = 'compassfont-title-text';
    var ACTIVITY_CLASS = 'compassfont-activity-text';
    var SKIP = '.quickeditlink, .icon, .accesshide, svg, img, i';

    var hasKey = function(obj, key) {
        return Object.prototype.hasOwnProperty.call(obj, key);
    };

    var loadPrefs = function() {
        try {
            var raw = window.localStorage.getItem(config.storageKey);
            var prefs = raw ? JSON.parse(raw) : {};
            return (prefs && typeof prefs === 'object') ? prefs : {};
        } catch (e) {
            return {};
        }
    };

    var savePrefs = function(prefs) {
        try {
            window.localStorage.setItem(config.storageKey, JSON.stringify(prefs));
        } catch (e) {
            // Storage unavailable (private mode, blocked): settings still apply to this page.
        }
    };

    // Keep only values that are on the allow-list, so stored data cannot inject arbitrary CSS.
    var sanitise = function(prefs) {
        var out = {};
        if (prefs.family && hasKey(config.fonts, prefs.family) && config.fonts[prefs.family]) {
            out.family = prefs.family;
        }
        if (prefs.size && config.sizes.indexOf(prefs.size) !== -1) {
            out.size = prefs.size;
        }
        if (typeof prefs.colour === 'string' && HEX.test(prefs.colour)) {
            out.colour = prefs.colour.toLowerCase();
        }
        return out;
    };

    // Wrap the words of an element in a span, leaving icons, edit links and other elements alone.
    var wrapText = function(node, className) {
        if (node.classList && node.classList.contains(className)) {
            return;
        }
        Array.prototype.slice.call(node.childNodes).forEach(function(child) {
            if (child.nodeType === 3 && child.textContent.trim() !== '') {
                var span = document.createElement('span');
                span.className = className;
                node.insertBefore(span, child);
                span.appendChild(child);
            } else if (child.nodeType === 1 && !child.matches(SKIP)) {
                wrapText(child, className);
            }
        });
    };

    // Wrap titles and activity names when the course has colours, or when an admin may preview them.
    var wrapTargets = function() {
        Array.prototype.forEach.call(document.querySelectorAll('#page .sectionname'), function(el) {
            wrapText(el, TITLE_CLASS);
        });
        Array.prototype.forEach.call(document.querySelectorAll('#page .activityname'), function(el) {
            wrapText(el, ACTIVITY_CLASS);
        });
    };

    // Set or clear one CSS custom property and the class that switches its rule on.
    var setOne = function(element, className, property, value) {
        if (value) {
            element.style.setProperty(property, value);
            element.classList.add(className);
        } else {
            element.style.removeProperty(property);
            element.classList.remove(className);
        }
    };

    var apply = function(prefs) {
        var root = document.documentElement;
        var body = document.body;

        setOne(body, 'compassfont-size', '--compassfont-size', prefs.size || null);
        setOne(body, 'compassfont-family', '--compassfont-family',
            prefs.family ? config.fonts[prefs.family] : null);
        setOne(body, 'compassfont-colour', '--compassfont-colour', prefs.colour || null);

        // Course colours: the saved values from the block instance.
        var section = (config.section && HEX.test(config.section)) ? config.section.toLowerCase() : null;
        var activity = (config.activity && HEX.test(config.activity)) ? config.activity.toLowerCase() : null;
        setOne(body, 'compassfont-section', '--compassfont-section', section);
        setOne(body, 'compassfont-activity', '--compassfont-activity', activity);
    };

    // Admin preview: show the course colours currently chosen in the form, without saving.
    var previewCourseColours = function() {
        var body = document.body;
        var sectionPicker = document.getElementById('compassfont-section');
        var activityPicker = document.getElementById('compassfont-activity');
        var useSection = document.querySelector('input[name="use_section"]');
        var useActivity = document.querySelector('input[name="use_activity"]');

        setOne(body, 'compassfont-section', '--compassfont-section',
            (sectionPicker && useSection && useSection.checked) ? sectionPicker.value : null);
        setOne(body, 'compassfont-activity', '--compassfont-activity',
            (activityPicker && useActivity && useActivity.checked) ? activityPicker.value : null);
    };

    // Set one learner preference. 'default' (or empty) clears it.
    var update = function(key, value) {
        var prefs = sanitise(loadPrefs());
        if (value && value !== 'default') {
            prefs[key] = value;
        } else {
            delete prefs[key];
        }
        prefs = sanitise(prefs);
        savePrefs(prefs);
        apply(prefs);
    };

    return {
        /**
         * @param {Object} options storageKey, fonts (key => css|null), sizes (array),
         *                         textDefault (hex), section and activity (hex or null, saved on the block),
         *                         canEdit (bool, admins and managers)
         */
        init: function(options) {
            config = options;

            wrapTargets();

            var prefs = config.canEdit ? sanitise(loadPrefs()) : {};
            apply(prefs);

            var family = document.getElementById('compassfont-family');
            var size = document.getElementById('compassfont-size');
            var colour = document.getElementById('compassfont-colour');

            if (family) {
                family.value = prefs.family || 'default';
                family.addEventListener('change', function() {
                    update('family', family.value);
                });
            }
            if (size) {
                size.value = prefs.size || 'default';
                size.addEventListener('change', function() {
                    update('size', size.value);
                });
            }
            // Text colour only applies while its "Use this colour" tick is on.
            var useColour = document.getElementById('compassfont-use-colour');
            var applyTextColour = function() {
                update('colour', (colour && useColour && useColour.checked) ? colour.value : null);
            };
            if (colour) {
                colour.value = prefs.colour || config.textDefault;
                colour.addEventListener('input', applyTextColour);
            }
            if (useColour) {
                useColour.checked = !!prefs.colour;
                useColour.addEventListener('change', applyTextColour);
            }

            // Admins: preview the course colour pickers live. Nothing is saved until the form is submitted.
            if (config.canEdit) {
                previewCourseColours();
                var previewIds = ['compassfont-section', 'compassfont-activity'];
                previewIds.forEach(function(id) {
                    var picker = document.getElementById(id);
                    if (picker) {
                        picker.addEventListener('input', previewCourseColours);
                    }
                });
                var checkboxes = document.querySelectorAll(
                    'input[name="use_section"], input[name="use_activity"]');
                Array.prototype.forEach.call(checkboxes, function(box) {
                    box.addEventListener('change', previewCourseColours);
                });
            }

            var reset = document.getElementById('compassfont-reset');
            if (reset) {
                reset.addEventListener('click', function() {
                    savePrefs({});
                    apply({});
                    if (family) {
                        family.value = 'default';
                    }
                    if (size) {
                        size.value = 'default';
                    }
                    if (colour) {
                        colour.value = config.textDefault;
                    }
                    if (useColour) {
                        useColour.checked = false;
                    }
                    // Admins: clear the course colours in the form and on the page. Save to store them.
                    if (config.canEdit) {
                        ['use_section', 'use_activity'].forEach(function(name) {
                            var box = document.querySelector('input[name="' + name + '"]');
                            if (box) {
                                box.checked = false;
                            }
                        });
                        ['compassfont-section', 'compassfont-activity'].forEach(function(id) {
                            var picker = document.getElementById(id);
                            if (picker) {
                                picker.value = '#000000';
                            }
                        });
                        previewCourseColours();
                    }
                });
            }
        }
    };
});
