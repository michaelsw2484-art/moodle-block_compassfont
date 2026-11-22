# CompassFont (block_compassfont)

Release 1.1.0. Moodle 4.5 to 5.3. GNU GPL v3 or later.

CompassFont is a lightweight Moodle block for course pages. It lets admins, managers, course creators and editing teachers choose the font type, font size, text colour, and section and activity font colours for a course. Everyone on the course sees them, on the course page and inside activities. Only people with the right permission see the block and its controls.

## Who sees what

- **Admins, managers, course creators and editing teachers** (anyone with `block/compassfont:sectioncolour`) see the block with its controls.
- **Learners, non-editing teachers and everyone else** do not see the block or any controls. They see the settings that were saved for the course.

## Controls (for people with the permission)

- **Font type** and **Font size**: choose one, or *Default* to keep the theme's font.
- **Text colour**, **Section font colour** and **Activity font colour**: a colour picker with a "Use this colour" tick. A colour only applies while its tick is on.
- **Save course settings**: stores the settings on the block, so everyone on the course sees them. Until then, changes are only a preview on your own screen.
- **Reset**: puts the form back to the defaults and previews that. Press **Save course settings** to store it.

## Where the settings apply

Font type, font size and text colour apply to body text on the course page and inside activities: paragraphs, lists, tables, labels, quiz questions and answers, feedback, forum posts and the written content of resources such as a page or book. Font type and size also apply to section titles and activity names on the course page. Icons, buttons, links and headings keep their look.

Section and activity colours apply on the course page, in the course index (the left-hand drawer), in the title of each section page and in the title of each activity page.

Content that Moodle shows in a frame from your own site, such as H5P and SCORM packages, gets the same font type, size and text colour. Frames from other sites (YouTube, Vimeo, external tools and LTI) cannot be changed by the browser, so they keep their own look.

## Permissions

- `block/compassfont:addinstance`: editing teachers and managers can add the block to a course page.
- `block/compassfont:sectioncolour`: editing teachers, course creators and managers (and admins) see the controls and can save course colours. To stop editing teachers using the controls, set this capability to *Not set* or *Prohibit* for the Teacher role.

## Scope

- The block can be added to course pages only. It is not offered on the Dashboard.
- Only one CompassFont block is allowed per course.
- Elements that set their own font or colour (for example code blocks, some buttons and links, or activities with their own styling) may not change.
- Font size sets body text to the chosen size. Headings and page titles keep their size.
- Colours are chosen for the whole course, so check that the text colour you choose still has enough contrast against your theme's background.

## Requirements

- Moodle 4.5 to 5.3. Tested on Boost-based themes (which use `#page`).

## Installation

1. Upload the zip in **Site administration > Plugins > Install plugins**, or copy the `compassfont` folder into `blocks/` (`public/blocks/` on Moodle 5.1 and later), so you have `blocks/compassfont/`.
2. Go to **Site administration > Notifications** (or run `php admin/cli/upgrade.php`).
3. Turn on editing on a course, add the **CompassFont** block, and set its colours.

## Testing

PHPUnit (font lists, permissions, block settings, the activity-page hook, the script settings and the privacy provider) and a smoke test of the block on a real site, on Moodle 4.5, 5.0, 5.1, 5.2 and 5.3 with MariaDB. Moodle Code Checker (`--standard=moodle`) reports no errors or warnings. The controls were checked with axe-core (WCAG 2.2 AA) with no issues found.

## Privacy

The plugin stores no personal data. The saved settings belong to the course, not to a person. It implements Moodle's Privacy API as a null provider.

## Support

Bug reports and questions: https://github.com/michaelsw2484-art/compass-plugins-support/issues

## Development notes

- JavaScript source is in `amd/src/compassfont.js`. The file in `amd/build/compassfont.min.js` is the minified build that Moodle loads.
- To rebuild the minified file after changing the source, run `npx grunt amd` from the Moodle root, with the plugin in place (add `--root=blocks/compassfont`, or `--root=public/blocks/compassfont` on Moodle 5.1 and later, to build just this plugin).
- Code is checked against the Moodle coding standard with phpcs and `--standard=moodle`.

## Changelog

- **1.1.0**: font type, font size and text colour are now course settings, saved with **Save course settings** and seen by everyone on the course (they were personal browser settings before). They now also apply inside activities, including quiz questions and answers, feedback, forum posts and same-site frames such as H5P and SCORM. Section and activity colours now also show in the course index and in section and activity page titles. Editing teachers can now change the settings (given to existing Teacher roles on upgrade). `amd/build/compassfont.min.js` is a properly minified build with a source map, and language strings are plain single-quoted strings.
- **1.0.3**: the personal text colour now also applies to the written content inside activities (for example the body of a page), not only on the course page. Font type and size are unchanged.
