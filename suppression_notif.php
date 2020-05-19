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
 * Notification deletion.
 *
 * @package    block_tableau_bord
 * @copyright  2020 PRN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $DB;
if (isset($_POST["id_activite"]) && isset($_POST["id_user"])) {
    $ajout = new stdClass();
    $ajout->id_user = $_POST["id_user"];
    $ajout->id_course_module = $_POST["id_activite"];

    $parametrenotif = array('id_user' => $_POST["id_user"], 'id_course_module' => $_POST["id_activite"] );
    $ajout->time_delete = time();

    if ($DB->delete_records('tdb_delete_notifications', $parametrenotif) == true) {
        $DB->insert_record("tdb_delete_notifications", $ajout);
    }
}
