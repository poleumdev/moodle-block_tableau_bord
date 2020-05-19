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
require_once($CFG->dirroot.'/blocks/tableau_bord/locallib.php');
require_once($CFG->dirroot.'/blocks/tableau_bord/notif.php');
require_once($CFG->dirroot.'/blocks/tableau_bord/gestionnaire.php');
require_once($CFG->dirroot.'/lib/weblib.php');
require_once($CFG->dirroot.'/blocks/tableau_bord/lib.php');
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
	function get_required_javascript(){
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
        global $USER, $CFG, $DB;

        require_once($CFG->dirroot.'/user/profile/lib.php');

        if($this->content !== NULL) {
            return $this->content;
        }

        $config = get_config('block_tableau_bord');

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $content = array();

        $updatemynumber = optional_param('mynumber', -1, PARAM_INT);
        if ($updatemynumber >= 0) {
            block_tableau_bord_update_mynumber($updatemynumber);
        }

        profile_load_custom_fields($USER);
        list($sortedcourses, $sitecourses, $totalcourses) = block_tableau_bord_get_sorted_courses();
        $overviews = block_tableau_bord_get_overviews($sitecourses);
	
        $renderer = $this->page->get_renderer('block_tableau_bord');
        if (!empty($config->showwelcomearea)) {
            require_once($CFG->dirroot.'/message/lib.php');
            $msgcount = message_count_unread_messages();
            $this->content->text = $renderer->welcome_area($msgcount);
        }

        // Number of sites to display.
        if ($this->page->user_is_editing() && empty($config->forcedefaultmaxcourses)) {
            $this->content->text .= $renderer->editing_bar_head($totalcourses);
        }

        if (empty($sortedcourses)) {
            $this->content->text .= get_string('nocourses','my');
        } else {
            // For each course, build category cache.
			//Parametres $sortedcourses et $overviews : liste triee des cours et les informations a afficher pour les activites pour chaque cours
            $this->content->text .= $renderer->tableau_bord($sortedcourses, $overviews);
			
            $this->content->text .= $renderer->hidden_courses($totalcourses - count($sortedcourses));
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
     *
     * @return bool if true then header will be visible.
     */
    public function hide_header() {
        // Hide header if welcome area is show.
        $config = get_config('block_tableau_bord');
		// on cache le header car on a déjà le titre du plugin avec moodle : 
        return 1;	//!empty($config->showwelcomearea);
    }
}