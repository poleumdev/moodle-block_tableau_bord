<?php
	require_once(dirname(__FILE__) . '/../../config.php');
	if(isset($_POST["id_activite"]) && isset($_POST["id_user"])){
		global $DB;
		$ajout = new stdClass();
		$ajout->id_user = $_POST["id_user"];
		$ajout->id_course_module = $_POST["id_activite"];
		
		$parametre_notif = array('id_user' => $_POST["id_user"],'id_course_module' => $_POST["id_activite"] );
		
		$ajout->time_delete = time();
	
		if($DB->delete_records('tdb_delete_notifications',$parametre_notif) == true){
			$DB->insert_record("tdb_delete_notifications",$ajout);
		}
		
	} 
?>