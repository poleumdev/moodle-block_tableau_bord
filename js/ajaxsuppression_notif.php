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

define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../../config.php');
global $DB;

// Require for context.
require_login();
require_sesskey();

$context = context_system::instance();
$PAGE->set_context($context);
$userid = optional_param('id_user', -1, PARAM_INT);
$coursemodule = optional_param('id_activite', -1, PARAM_INT);

$state = 'userid_invalid';
if ($userid != -1 && $coursemodule != -1) {
    if ($DB->record_exists('tdb_delete_notifications', array('id_user' => $userid, 'id_course_module' => $coursemodule))) {
        $rec = $DB->get_record('tdb_delete_notifications', array('id_user' => $userid, 'id_course_module' => $coursemodule));
        $rec->time_delete = time();
        $DB->update_record('tdb_delete_notifications', $rec);
        $state = 'update';
    } else {
        $state = 'enreg_notfound';
    }
}

$res = array();
$res['result'] = true;
$res['state'] = $state;
echo $OUTPUT->header();
echo json_encode($res);
