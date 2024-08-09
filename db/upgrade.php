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
 * Database upgrades for the mytrainings block.
 *
 * @package   block_mytrainings
 * @copyright 2022 Edunao SAS (contact@edunao.com)
 * @author    Remi Colet <nabil.hamdi@edunao.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

/**
 * Upgrade the block_mytrainings database.
 *
 * @param int $oldversion The version number of the plugin that was installed.
 * @return boolean
 * @throws ddl_exception
 * @throws ddl_table_missing_exception
 * @throws dml_exception
 */
function xmldb_block_mytrainings_upgrade($oldversion) {
    global $CFG, $DB;

    require_once($CFG->libdir . '/db/upgradelib.php'); // Core Upgrade-related functions.

    if ($oldversion < 2022101200) {
        $trainingfavourites = $DB->get_records('favourite', ['itemtype' => 'training_designer']);

        foreach ($trainingfavourites as $trainingfavourite) {
            $trainingfavourite->itemtype = 'favourite_training';
            $DB->update_record('favourite', $trainingfavourite);
        }

        $DB->delete_records('favourite', ['itemtype' => 'my_session']);
    }

    return true;
}
