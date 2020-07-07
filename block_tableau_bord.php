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
 * Course overview block
 *
 * @package    block_tableau_bord
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/blocks/tableau_bord/locallib.php');
require_once($CFG->dirroot.'/blocks/tableau_bord/notif.php');
require_once($CFG->dirroot.'/lib/weblib.php');
/**
 * Course overview block
 *
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_tableau_bord extends block_base {
    /**
     * Block initialization
     */
    public function get_required_javascript() {
        parent::get_required_javascript();

        $this->page->requires->jquery();
        $this->page->requires->jquery_plugin('ui');
        $this->page->requires->jquery_plugin('ui-css');
    }
    public function init() {
        $this->title   = get_string('pluginname', 'block_tableau_bord');
    }

    /**
     * Return contents of tableau_bord block
     *
     * @return stdClass contents of block
     */
    public function get_content() {
        global $USER, $CFG;

        require_once($CFG->dirroot.'/user/profile/lib.php');

        if ($this->content !== null) {
            return $this->content;
        }

        $config = get_config('block_tableau_bord');

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        profile_load_custom_fields($USER);
        $editmode = optional_param('editmode', 'off', PARAM_TEXT);

        list($sortedcourses, $sitecourses, $totalcourses) = block_tableau_bord_get_sorted_courses();
        $overviews = block_tableau_bord_get_overviews($sitecourses);

        $renderer = $this->page->get_renderer('block_tableau_bord');

        if (!empty($config->showwelcomearea)) {
            require_once($CFG->dirroot.'/message/lib.php');
            $msgcount = message_count_unread_messages();
            $this->content->text .= $renderer->welcome_area($msgcount);
        }

        if (empty($sortedcourses)) {
            $this->content->text .= "<b>" . get_string('nocourses', 'my') . "</b>";
        } else {
            if ($editmode == 'on') {
                $std = array();
                foreach ($sortedcourses as $course) {
                    $std[] = $course;
                }
                // Load script Ajax.
                $this->page->requires->js('/blocks/tableau_bord/js/scriptajax.js');
                $this->content->text .= '<a class="btn btn-primary" href="'.$CFG->wwwroot.'/my/index.php">';
                $this->content->text .= 'Enregistrer l\'ordre des cours</a>';
                $this->content->text .= $renderer->render_from_template('block_tableau_bord/lstcourse',
                                            array('std' => $std, 'wroot' => $CFG->wwwroot, 'userid' => $USER->id), null);
            } else {
                // List onglet.
                $lstonglet = array();
                foreach ($sortedcourses as $course) {
                    $coursefullname = $course->fullname;
                    $pattern = "/\[(20[0-9]{2}-20[0-9]{2})\]/";
                    if (preg_match($pattern, $coursefullname, $matches)) {
                        $lstonglet[$matches[1]] = $matches[1];
                    }
                }
                $arronglet = array();
                if (count($lstonglet) > 0) {
                    rsort($lstonglet);
                    foreach ($lstonglet as $ongl) {
                        $arronglet[$ongl] = array();
                    }
                    $horsonglet = array();
                    foreach ($sortedcourses as $course) {
                        $coursefullname = $course->fullname;
                        $pattern = "/\[(20[0-9]{2}-20[0-9]{2})\]/";
                        if (preg_match($pattern, $coursefullname, $matches)) {
                            $arronglet[$matches[1]][] = $course;
                        } else {
                            $horsonglet[] = $course;
                        }
                    }
                    $sortedcourses = array();
                    $cpt = 0;
                    foreach ($arronglet as $onglet) {
                        foreach ($onglet as $course) {
                            $sortedcourses[] = $course;
                        }
                        if ($cpt == 0) {
                            $cpt++;
                            foreach ($horsonglet as $course) {
                                $sortedcourses[] = $course;
                            }
                        }
                    }
                }
                $this->content->text .= '<div class="d-flex flex-row-reverse"><a  class="btn btn-primary" href="'.$CFG->wwwroot.'/my/index.php?editmode=on" title="Ordonnancer mes cours">';
                $this->content->text .= 'Modifier l\'ordre de mes cours</a></div>';
                $this->content->text .= $renderer->tableau_bord($sortedcourses, $overviews, $lstonglet);
                $this->content->text .= $renderer->hidden_courses($totalcourses - count($sortedcourses));
            }
        }
        return $this->content;
    }

    /**
     * Allow the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }

    /**
     * Locations where block can be displayed
     *
     * @return array
     */
    public function applicable_formats() {
        return array('my-index' => true);
    }

    /**
     * Sets block header to be hidden or visible
     * ex : $config = get_config('block_tableau_bord');!empty($config->showwelcomearea);
     * @return bool if true then header will be visible.
     */
    public function hide_header() {
        return 1;
    }
}
