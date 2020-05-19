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
 * Prints a particular instance of lightboxgallery
 *
 * @package   mod_lightboxgallery
 * @author    Adam Olley <adam.olley@netspot.com.au>
 * @copyright 2012 NetSpot Pty Ltd
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once($CFG->libdir.'/formslib.php');


// Modification du code de /mod/lightboxgallery/imageadd.php
class gestionnaire_image extends moodleform {

    public function definition() {

        global $COURSE;
        $mform =& $this->_form;
        $gallery = $this->_customdata;

        $mform->addElement('header', 'general', get_string('addimage', 'lightboxgallery'));
		
		// Cree la zone de depot de fichier
        $mform->addElement('filemanager', 'image', get_string('file'), '0',
                           array('maxbytes' => $COURSE->maxbytes, 'accepted_types' => array('web_image')));
        $mform->addRule('image', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('image', 'addimage', 'block_tableau_bord'); // Va chercher la phrase correspondant a addimage_help dans le fichier langue

        $mform->addElement('hidden', 'id',$COURSE->id);
        $mform->setType('id',PARAM_INT);

        $this->add_action_buttons(true, get_string('addimage', 'block_tableau_bord'));

    }

}

