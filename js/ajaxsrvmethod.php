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
 * Method call by ajax, that method store course order.
 *
 * @package    block_tableau_bord
 * @copyright  2020 Pole de Ressource Numerique de l'Universite du Mans
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
$userid = optional_param('userid', -1, PARAM_INT);
$courseorder = optional_param('courseorder', "", PARAM_RAW);

if (strlen($courseorder) > 1) {
    $courseorder = substr($courseorder, 0, strlen($courseorder) - 1);
}
$state = 'userid_invalid';
if ($userid != -1) {
    if ($DB->record_exists('user_preferences', array('userid' => $userid, 'name' => 'tableau_bord_course_order'))) {
        $rec = $DB->get_record('user_preferences', array('userid' => $userid, 'name' => 'tableau_bord_course_order'));
        $rec->value = $courseorder;
        $DB->update_record('user_preferences', $rec);
        $state = 'update';
    } else {
        $dataobject = new \stdClass();
        $dataobject->userid = $userid;
        $dataobject->name = 'tableau_bord_course_order';
        $dataobject->value = $courseorder;
        $DB->insert_record('user_preferences', $dataobject, true);
        $state = 'create';
    }
}

$res = array();
$res['result'] = true;
$res['state'] = $state;
echo $OUTPUT->header();
echo json_encode($res);
