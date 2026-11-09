# CompassFont (block_compassfont)

Release 1.0.2. Moodle 4.5 to 5.3. GNU GPL v3 or later.

CompassFont is a lightweight Moodle block for course pages. It lets admins, managers and course creators set the font colour of section titles and activity names for a course. Everyone on the course sees the colours. Only people with the right permission see the block and its controls.

## Who sees what

- **Admins, managers and course creators** (anyone with `block/compassfont:sectioncolour`) see the block with its controls.
- **Learners, teachers and everyone else** do not see the block or any controls. They see the section and activity colours that were saved for the course, and nothing else changes for them.

## Controls (for people with the permission)

- **Section font colour** and **Activity font colour**: a colour picker with a "Use this colour" tick. Changes preview on the page straight away. Nothing is stored until **Save course colours** is pressed.
- **Font type**, **Font size** and **Text colour**: personal settings for the person using the block. They change how the page looks in their own browser only (stored in localStorage) and are never saved to the course or the database. Text colour only applies while its "Use this colour" tick is on.
- **Reset**: clears those personal settings, and clears the course colour pickers in the form (press Save to store that).

Saved course colours are stored on the block instance, so every user on that course sees them.

Font type and size apply to body text, section titles and activity names. Text colour applies to body text only (paragraphs, list items and table cells), so icons, buttons and headings keep their look.

## Permissions

- `block/compassfont:addinstance`: editing teachers and managers can add the block to a course page.
- `block/compassfont:sectioncolour`: course creators and managers (and admins) see the controls and can save course colours.

Editing teachers can add the block but, unless you also give them `block/compassfont:sectioncolour`, they will not see its controls.

## Scope

- The block can be added to course pages only. It is not offered on the Dashboard.
- Only one CompassFont block is allowed per course.
- Styles target `#page`. Elements that set their own font or colour (for example code blocks, some buttons and links) may not change.
- Font size applies to the page's base text size. Headings that use fixed `rem` sizes scale less.
- Colours are chosen by admins, so check that the text colour you choose still has enough contrast against your theme's background.

## Requirements

- Moodle 4.5 to 5.3. Tested on Boost-based themes (which use `#page`).

## Installation

1. Upload the zip in **Site administration > Plugins > Install plugins**, or copy the `compassfont` folder into `blocks/` (`public/blocks/` on Moodle 5.1 and later), so you have `blocks/compassfont/`.
2. Go to **Site administration > Notifications** (or run `php admin/cli/upgrade.php`).
3. Turn on editing on a course, add the **CompassFont** block, and set its colours.

## Testing

PHPUnit (5 tests: font lists, permissions, block settings and the privacy provider) and a smoke test of the block on a real site, on Moodle 4.5, 5.0, 5.1, 5.2 and 5.3 with MariaDB. Moodle Code Checker (`--standard=moodle`) reports no errors or warnings. The controls were checked with axe-core (WCAG 2.2 AA) with no issues found.

## Privacy

The plugin stores no personal data on the server. Personal font settings stay in the browser (localStorage). The saved course colours belong to the course, not to a person. It implements Moodle's Privacy API as a null provider.

## Support

Bug reports and questions: https://github.com/michaelsw2484-art/compass-plugins-support/issues

## Development notes

- JavaScript source is in `amd/src/compassfont.js`. The file in `amd/build/compassfont.min.js` is the minified build that Moodle loads.
- To rebuild the minified file after changing the source, run `npx grunt amd` from the Moodle root, with the plugin in place (add `--root=blocks/compassfont`, or `--root=public/blocks/compassfont` on Moodle 5.1 and later, to build just this plugin).
- Code is checked against the Moodle coding standard with phpcs and `--standard=moodle`.
