<?php

	defined('MOODLE_INTERNAL') || die();

	require_once($CFG->libdir.'/filelib.php');
	require_once(dirname(__FILE__).'/locallib.php');
	
// Fonction appellee pour afficher un fichier contenu dans moodle. Effectue les verifications voulues avant l'affichage
function block_tableau_bord_pluginfile($course, $birecord_or_cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $DB, $CFG;
	// Verification du contextid (passage en int au prealable)
    if (intval($context->id) != CONTEXT_BLOCK) {
       send_file_not_found();
    }
	// Verification du filearea
    if ($filearea != 'image') {
        send_file_not_found();
    }

    $fs = get_file_storage();

    $filename = array_pop($args);
    $filepath = $args ? '/'.implode('/', $args).'/' : '/';

    if (!$file = $fs->get_file(CONTEXT_BLOCK, 'block_tableau_bord', 'image', 0, '/', $filename) or $file->is_directory()) {
        send_file_not_found();
    }

    $forcedownload = true;

	
	send_stored_file($file, 60*60, 0, $forcedownload, $options);
}