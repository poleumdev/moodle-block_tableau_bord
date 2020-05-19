<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
global $CFG;
// Si l'utilisateur a clique sur le bouton on active le drag and drop
if ( isset($_POST['ordre_cours']) && !empty($_POST['ordre_cours']) ) {
	mode_edition_cours();
	header("Location: ".$CFG->wwwroot.'/my');
}	
// Si l'utilisateur a clique sur le bouton valider alors on quitte le mode edition
elseif( isset($_POST['valider']) && !empty($_POST['valider'])){
	quitter_edition_cours();
	header("Location: ".$CFG->wwwroot.'/my');
} else {
	header("Location: ".$CFG->wwwroot.'/my');
}
?>