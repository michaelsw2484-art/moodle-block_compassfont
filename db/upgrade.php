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
 * Upgrade steps for block_compassfont.
 *
 * @package    block_compassfont
 * @copyright  2026 Michael Williams
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade the block.
 *
 * @param int $oldversion The version being upgraded from.
 * @return bool
 */
function xmldb_block_compassfont_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2026112200) {
        // Editing teachers can now use the controls and set course colours. Moodle only applies a
        // capability's default roles on install, so give it to roles based on the editing teacher
        // archetype here. Roles where an admin has already set this capability are left alone.
        $systemcontext = context_system::instance();
        foreach (get_archetype_roles('editingteacher') as $role) {
            $alreadyset = $DB->record_exists('role_capabilities', [
                'roleid' => $role->id,
                'contextid' => $systemcontext->id,
                'capability' => 'block/compassfont:sectioncolour',
            ]);
            if (!$alreadyset) {
                assign_capability('block/compassfont:sectioncolour', CAP_ALLOW, $role->id, $systemcontext->id);
            }
        }
        accesslib_clear_all_caches(true);

        upgrade_block_savepoint(true, 2026112200, 'compassfont');
    }

    return true;
}
