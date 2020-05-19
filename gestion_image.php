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
 * Gestion image.
 *
 * @package    block_tableau_bord
 * @copyright  2020 PRN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
global $CFG, $PAGE, $OUTPUT, $DB;
require_once($CFG->dirroot.'/blocks/tableau_bord/gestionnaire.php');

$PAGE->set_context(context_system::instance());
// Check user is logged in and capable of accessing the Overview.
require_login($course, false);

echo $OUTPUT->header();

$mform = new gestionnaire_image(null);
// Sauvegarde de l'id du cours dans un cookie pour le rechargement de la page lors de l'ajout de l'image.
// Probleme si l'utilisateur ouvre plusieurs page de modif d'images a la suite.
// Si un bouton annuler a ete cliqué.
if ($mform->is_cancelled()) {
    echo "1";
} else if (($formdata = $mform->get_data()) && confirm_sesskey()) {
    // Si un bouton valider a ete clique et qu'il renvoie bien un/des fichier(s).
    require_once($CFG->dirroot . '/lib/uploadlib.php');
    $fs = get_file_storage();// Recupere les informations pour le stockage de fichiers
    $draftid = file_get_submitted_draft_itemid('image');// Parametre = nom du formulaire. Recupere l'itemid du gestionnaire.
    $files = $fs->get_area_files(get_context_instance(CONTEXT_USER, $USER->id)->id, 'user', 'draft', $draftid, 'id DESC', false));
    $storedfile = reset($files); // Recupere le premier fichier du tableau.

    if ($storedfile->is_valid_image()) {
        $filename = $storedfile->get_filename();
        $fileinfo = array(
            'contextid'     => CONTEXT_BLOCK,
            'component'     => 'block_tableau_bord',
            'filearea'      => 'image',
            'itemid'        => 0,
            'filepath'      => '/',
            'filename'      => $filename);
        // Ajoute l'image a la bdd et au systeme de fichier avec les parametres definis ci-dessus.
        if (!$fs->get_file(CONTEXT_BLOCK, 'block_tableau_bord', 'image', 0, '/', $filename)) {
            $storedfile = $fs->create_file_from_storedfile($fileinfo, $storedfile);
        }
    }
}
$mform->display();
echo $OUTPUT->footer();