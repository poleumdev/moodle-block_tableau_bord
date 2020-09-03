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
 * Sets user preference for maximum courses to be displayed in tableau_bord block
 *
 * @param int $number maximum courses which should be visible
 */
function block_tableau_bord_update_mynumber($number) {
    set_user_preference('tableau_bord_number_of_courses', $number);
}

/**
 * Sets user course sorting preference in tableau_bord block
 *
 * @param array $sortorder sort order of course
 */
function block_tableau_bord_update_myorder($sortorder) {
    set_user_preference('tableau_bord_course_order', serialize($sortorder));
}

/**
 * Returns shortname of activities in course
 *
 * @param int $courseid id of course for which activity shortname is needed
 * @return string|bool list of child shortname
 */
function block_tableau_bord_get_child_shortnames($courseid) {
    global $DB;
    $ctxselect = context_helper::get_preload_record_columns_sql('ctx');
    $sql = "SELECT c.id, c.shortname, $ctxselect
            FROM {enrol} e
            JOIN {course} c ON (c.id = e.customint1)
            JOIN {context} ctx ON (ctx.instanceid = e.customint1)
            WHERE e.courseid = :courseid AND e.enrol = :method AND ctx.contextlevel = :contextlevel ORDER BY e.sortorder";
    $params = array('method' => 'meta', 'courseid' => $courseid, 'contextlevel' => CONTEXT_COURSE);

    if ($results = $DB->get_records_sql($sql, $params)) {
        $shortnames = array();
        // Preload the context we will need it to format the category name shortly.
        foreach ($results as $res) {
            context_helper::preload_from_record($res);
            $context = context_course::instance($res->id);
            $shortnames[] = format_string($res->shortname, true, $context);
        }
        $total = count($shortnames);
        $suffix = '';
        if ($total > 10) {
            $shortnames = array_slice($shortnames, 0, 10);
            $diff = $total - count($shortnames);
            if ($diff > 1) {
                $suffix = get_string('shortnamesufixprural', 'block_tableau_bord', $diff);
            } else {
                $suffix = get_string('shortnamesufixsingular', 'block_tableau_bord', $diff);
            }
        }
        $shortnames = get_string('shortnameprefix', 'block_tableau_bord', implode('; ', $shortnames));
        $shortnames .= $suffix;
    }

    return isset($shortnames) ? $shortnames : false;
}

/**
 * Returns maximum number of courses which will be displayed in tableau_bord block
 *
 * @return int maximum number of courses
 */
function block_tableau_bord_get_max_user_courses() {
    // Get block configuration.
    $config = get_config('block_tableau_bord');
    $limit = $config->defaultmaxcourses;

    // If max course is not set then try get user preference.
    if (empty($config->forcedefaultmaxcourses)) {
        $limit = get_user_preferences('tableau_bord_number_of_courses', $limit);
    }
    return $limit;
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

// Permet a l'utilisateur de passer en mode edition des cours et ainsi d'activer le drag and drop.
function mode_edition_cours() {
    global $USER;
    // Passe la variable qui indique que l'utilisateur est en cours de modification a vrai (sinon ca la cree).
    $USER->userediting_course = true;
}

function quitter_edition_cours() {
    global $USER;
    // Passe la variable d'edition a faux.
    $USER->userediting_course = false;
}