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
 * CompassFont: course-wide font type, font size, text colour, and section and activity
 * font colours, chosen by people with the permission and seen by everyone on the course.
 *
 * The settings are saved on the block instance and passed in from PHP. Font size and the
 * other settings are applied using CSS custom properties defined in styles.css. People who
 * can change the settings get a live preview: changing a control updates the page straight
 * away, and nothing is stored until they press "Save course settings".
 *
 * Section titles and activity names have their words wrapped in a span so only
 * the text is coloured, not the icons or edit pencils inside them. This covers the
 * course page, the course index, section pages and the title of each activity page.
 *
 * On activity pages and other course pages, where the block is not shown, the same
 * settings are applied by applyOutside(). Content that Moodle shows in a frame from
 * the same site (for example H5P or SCORM) is styled too. Frames from other sites
 * (YouTube, Vimeo, external tools) cannot be changed by the browser.
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
    var SKIP = '.quickeditlink, .icon, .accesshide, .badge, svg, img, i';
    var FRAME_STYLE_ID = 'block_compassfont-frame-style';

    // Body text inside frames. Keep in step with the TEXT list in styles.css.
    var FRAME_TEXT = 'p, li, td, th, dt, dd, blockquote, label, figcaption, caption, .no-overflow';

    // Current values: personal settings (family, size, colour) and course colours (section, activity).
    var active = {family: null, size: null, colour: null, section: null, activity: null};

    // Documents being styled: the page itself and any same-site frames found in it.
    var docs = [];
    var seenFrames = typeof WeakSet === 'function' ? new WeakSet() : null;
    var observer = null;
    var pending = null;

    var hasKey = function(obj, key) {
        return Object.prototype.hasOwnProperty.call(obj, key);
    };

    var forEach = function(list, fn) {
        Array.prototype.forEach.call(list, fn);
    };

    var cleanHex = function(value) {
        return (typeof value === 'string' && HEX.test(value)) ? value.toLowerCase() : null;
    };

    // Wrap the words of an element in a span, leaving icons, edit links and other elements alone.
    var wrapText = function(node, className) {
        if (node.classList && node.classList.contains(className)) {
            return;
        }
        Array.prototype.slice.call(node.childNodes).forEach(function(child) {
            if (child.nodeType === 3 && child.textContent.trim() !== '') {
                var span = child.ownerDocument.createElement('span');
                span.className = className;
                node.insertBefore(span, child);
                span.appendChild(child);
            } else if (child.nodeType === 1 && !child.matches(SKIP)) {
                wrapText(child, className);
            }
        });
    };

    var wrapAll = function(selector, className) {
        forEach(document.querySelectorAll(selector), function(el) {
            wrapText(el, className);
        });
    };

    // Wrap section titles and activity names wherever they appear on this page.
    var wrapTargets = function() {
        var pageId = document.body.id || '';

        // Course page and section page content.
        wrapAll('#page .sectionname', TITLE_CLASS);
        wrapAll('#page .activityname', ACTIVITY_CLASS);

        // Course index (the left-hand drawer), on every page of the course.
        wrapAll('.courseindex .courseindex-section-title .courseindex-link', TITLE_CLASS);
        wrapAll('.courseindex .courseindex-link[data-for="cm_name"], .courseindex .courseindex-cmitem .courseindex-link',
            ACTIVITY_CLASS);

        // The page title: the activity name on activity pages, the section name on section pages.
        if (pageId.indexOf('page-mod-') === 0) {
            wrapAll('#page-header .page-header-headings h1, #page-header .page-header-headings h2', ACTIVITY_CLASS);
        } else if (pageId.indexOf('page-course-view-section') === 0 || pageId.indexOf('page-course-section') === 0) {
            wrapAll('#page-header .page-header-headings h1, #page-header .page-header-headings h2', TITLE_CLASS);
        }
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

    // Apply the current values to one document.
    var paint = function(doc) {
        var body = doc.body;
        if (!body) {
            return;
        }
        setOne(body, 'compassfont-size', '--compassfont-size', active.size);
        setOne(body, 'compassfont-family', '--compassfont-family', active.family);
        setOne(body, 'compassfont-colour', '--compassfont-colour', active.colour);
        setOne(body, 'compassfont-section', '--compassfont-section', active.section);
        setOne(body, 'compassfont-activity', '--compassfont-activity', active.activity);
    };

    // Apply the current values to the page and every frame being styled.
    var paintAll = function() {
        docs = docs.filter(function(doc) {
            return doc.defaultView && doc.body;
        });
        docs.forEach(paint);
    };

    // The rules used inside frames, which do not load the block's styles.css.
    var frameCss = function() {
        var text = ':is(' + FRAME_TEXT + ')';
        return 'body.compassfont-family ' + text + ' {font-family: var(--compassfont-family) !important;} ' +
            'body.compassfont-size ' + text + ' {font-size: var(--compassfont-size) !important;} ' +
            'body.compassfont-colour ' + text + ' {color: var(--compassfont-colour) !important;} ';
    };

    // Start styling a frame's document, if the browser lets us (same site only).
    var adoptFrame = function(frame) {
        var doc = null;
        try {
            doc = frame.contentDocument;
        } catch (e) {
            doc = null;
        }
        if (!doc || !doc.body || docs.indexOf(doc) !== -1) {
            return;
        }
        if (!doc.getElementById(FRAME_STYLE_ID)) {
            var style = doc.createElement('style');
            style.id = FRAME_STYLE_ID;
            style.textContent = frameCss();
            (doc.head || doc.documentElement).appendChild(style);
        }
        docs.push(doc);
        paint(doc);
        // eslint-disable-next-line no-use-before-define
        watchDocument(doc);
    };

    var hookFrame = function(frame) {
        if (seenFrames) {
            if (seenFrames.has(frame)) {
                return;
            }
            seenFrames.add(frame);
        } else if (frame.getAttribute('data-compassfont')) {
            return;
        }
        frame.setAttribute('data-compassfont', '1');
        // A frame gets a new document each time it loads, so adopt it again on every load.
        frame.addEventListener('load', function() {
            adoptFrame(frame);
        });
        adoptFrame(frame);
    };

    // Watch a document for frames added later (H5P, for example, builds its frame after loading).
    var watchDocument = function(doc) {
        forEach(doc.querySelectorAll('iframe'), hookFrame);
        if (typeof MutationObserver !== 'function' || !doc.body) {
            return;
        }
        new MutationObserver(function() {
            forEach(doc.querySelectorAll('iframe'), hookFrame);
        }).observe(doc.body, {childList: true, subtree: true});
    };

    // The course index and other parts of the page are redrawn by Moodle after loading,
    // so wrap new titles and names, and look for new frames, whenever the page changes.
    var watchPage = function() {
        if (observer || typeof MutationObserver !== 'function') {
            return;
        }
        observer = new MutationObserver(function() {
            if (pending) {
                return;
            }
            pending = window.setTimeout(function() {
                pending = null;
                observer.disconnect();
                wrapTargets();
                forEach(document.querySelectorAll('iframe'), hookFrame);
                observer.observe(document.body, {childList: true, subtree: true, characterData: true});
            }, 100);
        });
        observer.observe(document.body, {childList: true, subtree: true, characterData: true});
    };

    // Set up the page: wrap names, start styling frames and watch for changes.
    var start = function() {
        docs = [document];
        wrapTargets();
        forEach(document.querySelectorAll('iframe'), hookFrame);
        watchPage();
    };

    // Use a set of settings: family is a CSS font stack, size a CSS value, the rest hex colours.
    var setActive = function(values) {
        active.family = (typeof values.family === 'string' && values.family) ? values.family : null;
        active.size = (values.size && config.sizes.indexOf(values.size) !== -1) ? values.size : null;
        active.colour = cleanHex(values.colour);
        active.section = cleanHex(values.section);
        active.activity = cleanHex(values.activity);
        paintAll();
    };

    var byId = function(id) {
        return document.getElementById(id);
    };

    // A colour from the form, only while its "Use this colour" tick is on.
    var formColour = function(name) {
        var picker = byId('compassfont-' + name);
        var tick = byId('compassfont-' + name + '-use');
        return (picker && tick && tick.checked) ? picker.value : null;
    };

    // Live preview of the form, without saving.
    var preview = function() {
        var family = byId('compassfont-family');
        var size = byId('compassfont-size');
        var key = family ? family.value : 'default';
        setActive({
            family: (hasKey(config.fonts, key) && config.fonts[key]) ? config.fonts[key] : null,
            size: size ? size.value : null,
            colour: formColour('colour'),
            section: formColour('section'),
            activity: formColour('activity')
        });
    };

    // Reset puts the form back to the site defaults and previews that. Save to store it.
    var resetForm = function() {
        ['compassfont-family', 'compassfont-size'].forEach(function(id) {
            var select = byId(id);
            if (select) {
                select.value = 'default';
            }
        });
        ['colour', 'section', 'activity'].forEach(function(name) {
            var picker = byId('compassfont-' + name);
            var tick = byId('compassfont-' + name + '-use');
            if (picker) {
                picker.value = config.textDefault || '#000000';
            }
            if (tick) {
                tick.checked = false;
            }
        });
        preview();
    };

    return {
        /**
         * Apply the course settings on activity pages and other course pages, where the block
         * itself is not shown. Everyone on the course gets them.
         *
         * @param {Object} options fonts (key => css|null), sizes (array), family (css or null),
         *                         size (css or null), colour, section and activity (hex or null)
         */
        applyOutside: function(options) {
            config = options;
            start();
            setActive(config);
        },

        /**
         * Apply the course settings on the course page, and set up the live preview for people
         * who can change them.
         *
         * @param {Object} options as for applyOutside, plus canEdit (bool) and textDefault (hex)
         */
        init: function(options) {
            config = options;
            start();
            setActive(config);

            if (!config.canEdit) {
                return;
            }
            var form = byId('compassfont-form');
            if (form) {
                form.addEventListener('input', preview);
                form.addEventListener('change', preview);
            }
            var reset = byId('compassfont-reset');
            if (reset) {
                reset.addEventListener('click', resetForm);
            }
        }
    };
});
