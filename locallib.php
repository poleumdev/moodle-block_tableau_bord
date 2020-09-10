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
 * Helper functions for tableau_bord block
 *
 * @package    block_tableau_bord
 * @copyright  2012 Adam Olley <adam.olley@netspot.com.au>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Display overview for courses
 *
 * @param array $courses courses for which overview needs to be shown
 * @return array html overview
 */
function block_tableau_bord_get_overviews($coursesover) {
    $notification = array();
    if ($modules = get_plugin_list('mod')) {

        // Pour chaque cours.
        foreach ($coursesover as $courses) {
            // Pour chaque type d'activite existante on recherche si le cours necessite des notifications.
            foreach ($modules as $mod => $fname) {
                // Ajoute les notifications dans le tableau $notification.
                creer_notif($courses, $notification, $mod);
            }
        }
    }
    return $notification;
}

/**
 * Return sorted list of user courses
 *
 * @return array list of sorted courses and count of courses.
 * Renvoi la liste des cours dans l'ordre defini par les preferences de l'utilisateur ($USER->preferences)
 */
function block_tableau_bord_get_sorted_courses() {
    global $USER;

    $courses = enrol_get_my_courses("enablecompletion");
    $site = get_site();

    if (array_key_exists($site->id, $courses)) {
        unset($courses[$site->id]);
    }

    foreach ($courses as $c) {
        if (isset($USER->lastcourseaccess[$c->id])) {
            $courses[$c->id]->lastaccess = $USER->lastcourseaccess[$c->id];
        } else {
            $courses[$c->id]->lastaccess = 0;
        }
    }

    // Get remote courses.
    $remotecourses = array();
    if (is_enabled_auth('mnet')) {
        $remotecourses = get_my_remotecourses();
    }
    // Remote courses will have -ve remoteid as key, so it can be differentiated from normal courses.
    foreach ($remotecourses as $id => $val) {
        $remoteid = $val->remoteid * -1;
        $val->id = $remoteid;
        $courses[$remoteid] = $val;
    }

    $order = array();
    if (!is_null($usersortorder = get_user_preferences('tableau_bord_course_order'))) {
        $order = explode (",", $usersortorder);
    }

    $update = false;
    $sortedcourses = array();
    foreach ($order as $cid) {
        $ind = intval($cid);
        // Make sure user is still enroled.
        if (isset($courses[$ind])) {
            $sortedcourses[] = $courses[$ind];
            $c = $courses[$ind];
            $c->inplace = true;
            $courses[$ind] = $c;
        } else {
            $update = true;
        }
    }

    // Append unsorted courses if limit allows.
    foreach ($courses as $c) {
        if (!isset($c->inplace)) {
            $sortedcourses[] = $c;
            $update = true;
        }
    }

    // From list extract site courses for overview.
    $sitecourses = array();
    foreach ($sortedcourses as $key => $course) {
        if ($course->id > 0) {
            $sitecourses[$key] = $course;
        }
    }

    if ($update) {
        $neworder = "";
        foreach ($sortedcourses as $course) {
            $neworder .= $course->id . ",";
        }

        if (strlen($neworder) > 0) {
            $neworder = substr($neworder, 0, strlen($neworder) - 1);
            set_user_preference('tableau_bord_course_order', $neworder);
        } else {
            unset_user_preference('tableau_bord_course_order');
        }
    }

    return array($sortedcourses, $sitecourses, count($courses));
}

