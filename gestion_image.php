<?php
	require_once('../../config.php');
	global $CFG,$PAGE,$OUTPUT,$DB;
	require_once($CFG->dirroot.'/blocks/tableau_bord/gestionnaire.php');

$PAGE->set_context(context_system::instance()); // ou require_login() mais ne fonctionne pas

echo $OUTPUT->header();

	$mform = new gestionnaire_image(null);
	// Sauvegarde de l'id du cours dans un cookie pour le rechargement de la page lors de l'ajout de l'image
	// Probleme si l'utilisateur ouvre plusieurs page de modif d'images a la suite
	// Si un bouton annuler a ete clique
	if ($mform->is_cancelled()) {
		echo "1";
	} else if (($formdata = $mform->get_data()) && confirm_sesskey()) { //Si un bouton valider a ete clique et qu'il renvoie bien un/des fichier(s)
		require_once($CFG->dirroot . '/lib/uploadlib.php');
		$fs = get_file_storage();// Recupere les informations pour le stockage de fichiers
		$draftid = file_get_submitted_draft_itemid('image');// parametre = nom du formulaire. Recupere l'itemid du gestionnaire
		if (!$files = $fs->get_area_files( 
			get_context_instance(CONTEXT_USER, $USER->id)->id, 'user', 'draft', $draftid, 'id DESC', false)) {
			//Si il n'y a pas de fichier dans le gestionnaire renvoyer_ vers une page ?
		}
		$stored_file = reset($files); // recupere le premier fichier du tableau
		
		// Parametres
		if ($stored_file->is_valid_image()) {
			$filename = $stored_file->get_filename();
			$fileinfo = array(
				'contextid'     => CONTEXT_BLOCK,
				'component'     => 'block_tableau_bord',
				'filearea'      => 'image',
				'itemid'        => 0,
				'filepath'      => '/',
				'filename'      => $filename
			);
			// Ajoute l'image a la bdd et au systeme de fichier avec les parametres definis ci-dessus
			if (!$fs->get_file(CONTEXT_BLOCK, 'block_tableau_bord', 'image', 0, '/', $filename)) {
				$stored_file = $fs->create_file_from_storedfile($fileinfo, $stored_file);
			}
			
			/*// Parametres de l'image voulue dans la table files
			$parametre_image = array('contextid' => $fileinfo['contextid'],'component' => $fileinfo['component'],'filearea' => $fileinfo['filearea'],'itemid' => $fileinfo['itemid'],'filepath' => $fileinfo['filepath'],'filename' => $fileinfo['filename']);
			// On recupere l'enregistrement correspondant a l'image dans la table files
			if(($file = $DB->get_record('files',$parametre_image)) == true ){
				// Parametres d'ajout a la table course_image
				$parametre_ajout = array('id_cours' => $_COOKIE['id_cours'] ,'id_image' => $file->id);
				// Parametre pour verifier si l'id du cours est present
				$parametre_id_cours = array('id_cours' => $_COOKIE['id_cours']);
				//Si le cours n'a pas d'image de lie on ajoute le lien a la table
				if(($image = $DB->get_record("course_image",$parametre_id_cours)) == false){
					$DB->insert_record("course_image",$parametre_ajout);
				} else {
				// Sinon on supprime le lien pour y ajouter le nouveau
					$DB->delete_records("course_image",$parametre_id_cours);
					$DB->insert_record("course_image",$parametre_ajout);
				}
			}
			*/
		}
	}
	$mform->display();
echo $OUTPUT->footer();