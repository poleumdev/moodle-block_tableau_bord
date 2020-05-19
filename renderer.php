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
* tableau_bord block rendrer
*
* @package    block_tableau_bord
* @copyright  2012 Adam Olley <adam.olley@netspot.com.au>
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
defined('MOODLE_INTERNAL') || die;

/**
* tableau_bord block rendrer
*
* @copyright  2012 Adam Olley <adam.olley@netspot.com.au>
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

// Pour recuperer l'url de l'image
require_once($CFG->dirroot.'/lib/weblib.php');
require_once($CFG->dirroot.'/lib/completionlib.php'); 

class block_tableau_bord_renderer extends plugin_renderer_base {

    /**
    * Construct contents of tableau_bord block
    *
    * @param array $courses list of courses in sorted order
    * @param array $overviews list of course overviews
    * @return string html to be displayed in tableau_bord block
    */
    public function tableau_bord($courses, $overviews) {
        global $CFG, $USER, $PAGE, $OUTPUT, $DB;
        //$this->gestionnaire_image();
        $moodleurl = new moodle_url(null);
		
        // Recuperer le lien url de l'image
        //$urlimage = $moodleurl->make_pluginfile_url(CONTEXT_BLOCK, 'block_tableau_bord','image', 0, '/','arbre.jpg');
		
        $html = '<script src="'.$CFG->wwwroot.'/blocks/tableau_bord/Chart.js-master/Chart.js'.'"></script>
            <script src="'.$CFG->wwwroot.'/blocks/tableau_bord/graphes.js'.'"></script>
        <script src="'.$CFG->wwwroot.'/blocks/tableau_bord/xhr.js'.'"></script>';
	
        // Afficher un bouton d'aide
        //$html .= $OUTPUT->help_icon('progress','block_tableau_bord');
		


        // Affiche les boutons du menu
        $html .= $this->menu($courses);
		
		
        $config = get_config('block_tableau_bord');
        $ismovingcourse = false;
        $courseordernumber = 0;
        $maxcourses = count($courses);
		
        // $userediting : booleen vrai pour l'utilisateur est en cours de modification de l'ordre des cours, faux sinon
        $userediting = !empty($USER->userediting_course) && $USER->userediting_course == true;
		
        // Bouton pour valider le choix une fois que l'utilisateur a reorganise ses cours
        /*if($userediting){
            $html .='
            <form method="post" action="../blocks/tableau_bord/ordre_cours.php">
            <p>
            <input type="submit" value="Valider" title="validerCours" name="valider" />
            </p>
            </form>';
            }*/
		

            // Intialise string/icon etc if user is editing and courses > 1
           /* if ($this->page->user_is_editing() && (count($courses) > 1)) {
                $userediting = true;
                $this->page->requires->js_init_call('M.lock_tableau_bord.add_handles');

                // Check if course is moving
                $ismovingcourse = optional_param('movecourse', FALSE, PARAM_BOOL);
                $movingcourseid = optional_param('courseid', 0, PARAM_INT);
            }*/
        

            // Render first movehere icon.
            // Quand on on ne peut pas utiliser le drag and drop
            // Affiche la première icone de deplacement ainsi que la phrase pour annuler.
            if ($ismovingcourse) {
                // Remove movecourse param from url.
                $this->page->ensure_param_not_in_url('movecourse');

                // Show moving course notice, so user knows what is being moved.
                $html .= $this->output->box_start('notice');
                $a = new stdClass();
                $a->fullname = $courses[$movingcourseid]->fullname;
                $a->cancellink = html_writer::link($this->page->url, get_string('cancel'));
                $html .= get_string('movingcourse', 'block_tableau_bord', $a);
                $html .= $this->output->box_end();
                $moveurl = new moodle_url('/blocks/tableau_bord/move.php',
                array('sesskey' => sesskey(), 'moveto' => 0, 'courseid' => $movingcourseid));
                // Create move icon, so it can be used.
                $movetofirsticon = html_writer::empty_tag('img',
                array('src' => $this->output->image_url('movehere'),
                'alt' => get_string('movetofirst', 'block_tableau_bord', $courses[$movingcourseid]->fullname),
                'title' => get_string('movehere')));
                $moveurl = html_writer::link($moveurl, $movetofirsticon);
                $html .= html_writer::tag('div', $moveurl, array('class' => 'movehere'));
            }
		
        
        
            /* add jjupin "Onglets annualisation"
            * Pour chaque année, on ajoute un onglet
            * On parse chaque nom de cours pour récuperer l'année => [20xx-20xx]
            * Utilise jQuery pour la contruction des onglets
            */
        
            $arrayDate = array(); //sera complété à chaque nouvelle année trouvée
            // on parcours les cours pour récupérer toutes les "années" différentes
            foreach ($courses as $key => $course) {
                $courseFullName = $course->fullname;
                $pattern = "/\[(20[0-9]{2}-20[0-9]{2})\]/";
            
                if (preg_match($pattern, $courseFullName, $matches)){
                    $arrayDate[$matches[1]] = $matches[1]; // si on trouve on l'ajoute au tab
                }
            }
            // ajouter une liste HTML avec les dates (pour la construction des onglets)
            if (!empty($arrayDate)){
                arsort($arrayDate);
                $html .= '<div id="tabs">';
                $html .= "<ul class='tabs'>";
                //pour chaque "année" on ajoute une ligne, qui sera avec jQuery transformé en onglet
                foreach($arrayDate as $date){
                    $html .= '<li><a href="#tabs-'.$date.'" class="tab'.$date.'">'.$date.'</a></li>';
                }
                $html .= "</ul>";
	    
                // code jQuery
                $html .='<script>window.onload = function() {';
                    foreach($arrayDate as $date){ // pour chaque "année, pour les rassembler dans un div"
                        $html .='$( ".'.$date.'" ).wrapAll( "<div id=\'tabs-'.$date.'\' />");';
                    }
                    $html .='$( "#tabs" ).tabs();';
                    
                    // Selectionner un onglet en particulier, ici 2018-2019 | add jjupin
                    //$html .='$(".tab2018-2019").click();';
                    // END: Selectionner un onglet en particulier, ici 2018-2019 | add jjupin
                    
                    // ajout d'un texte pour prévenir de la nouveauté
                    $html .='$(".welcome_title").append("<span class=\"nouveautetdb\">Les cours sont  triés par année universitaire dans des onglets</span>");';
                    $html .='};</script>';
                }
                // fin add jjupin
    
                foreach ($courses as $key => $course) {
            
            
                    /* add jjupin "Onglets annualisation"
                    * Pour chaque cours, ajouter une classe en fonction de l'année pour créer des onglets
                    * On parse chaque nom de cours pour récuperer l'année => [20xx-20xx]
                    *
                    */
            
                    $courseFullName = $course->fullname;
                    $courseClass = ""; //sera complété si une date est trouvé
                    $pattern = "/\[(20[0-9]{2}-20[0-9]{2})\]/";
            
                    if (preg_match($pattern, $courseFullName, $matches)){
                        $courseClass = $matches[1];
                    }else{
                        // sinon on donne l'année en cours (la plus élevée = la 1e dans le tableau qui a été trié)
                        $courseClass = reset($arrayDate); 
                    }
                    // end add jjupin
            
            
            
                   
                    // If moving course, then don't show course which needs to be moved.
                    // Pendant que l'on bouge un cours, celui ci n'est plus affiche a sa place initiale (lorsqu'il n'y a pas le drag and drop)
                    if ($ismovingcourse && ($course->id == $movingcourseid)) {
                        continue;
                    }
                    // coursebox -> le bloc d'un cours 
                    //$html .= $this->output->box_start('coursebox', "course-{$course->id}");
			
					// add jjupin 31/01/17 => ajouter une classe CSS si le cours est masqué (le griser)
					if (empty($course->visible)) {
						$courseClass .=' course_not_visible ';
					}
					// end add jjupin 31/01/17
			
			
                    /* Defini les classes des cours en fonction de la valeur presente dans le cookie s'il existe.
                    * Sinon defini par defaut la classe coursebox qui correspond a un affichage des cours en liste*/
                    if(!empty($_COOKIE['disposition'])){
                        $html .= $this->output->box_start($_COOKIE['disposition']." ".$courseClass, "course-{$course->id}"); // add jjupin
                    }else{
						
						
                        $html .= $this->output->box_start('coursebox '.$courseClass, "course-{$course->id}"); // add jjupin
                    }
			
                   
                        // De base
                        //$html .= html_writer::start_tag('div', array('class' => 'course_title'));
                        // Pour le theme EAD
                        $html.= '<div class="cours-titre">';
						
						// add jjupin 31/01/17 => ajouter une information si le cours est masqué
						if (empty($course->visible)) {
							 $html.= '(COURS MASQUÉ) ';
						}
						// end add jjupin 31/01/17
						
                        // If user is editing, then add move icons.
                        // Ajoute l'icone qui permet de deplacer les cours lorsque le drag and drop est disponible
                        if ($userediting && !$ismovingcourse) {
                            $moveicon = html_writer::empty_tag('img',
                            array('src' => $this->image_url('t/move')->out(false),
                            'alt' => get_string('movecourse', 'block_tableau_bord', $course->fullname),
                            'title' => get_string('move')));
                            $moveurl = new moodle_url($this->page->url, array('sesskey' => sesskey(), 'movecourse' => 1, 'courseid' => $course->id));
                            $moveurl = html_writer::link($moveurl, $moveicon);
                            $html .= html_writer::tag('div', $moveurl, array('class' => 'move'));
                        }

                        // No need to pass title through s() here as it will be done automatically by html_writer.
                        $attributes = array('title' => $course->fullname);
                        if ($course->id > 0) {
                            if (empty($course->visible)) {
                                $attributes['class'] = 'dimmed';
                            }
                            //Affiche les titres pour chaque cours avec le lien du cours dessus (De base, avec lien du cours sur le titre)
                            //$courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
                            //$coursefullname = format_string($course->fullname, true, $course->id);
                            //$link = html_writer::link($courseurl, $coursefullname, $attributes);
                            //$html .= $this->output->heading($link, 2, 'title');
				
                            // Pour l'ead, sans lien sur le titre
                            $coursefullname = format_string($course->fullname, true, $course->id); //on peutmettre fullname/shortname 
                            $html .= $coursefullname;
                        } else {
                            $html .= $this->output->heading(html_writer::link(
                            new moodle_url('/auth/mnet/jump.php', array('hostid' => $course->hostid, 'wantsurl' => '/course/view.php?id='.$course->remoteid)),
                            format_string($course->shortname, true), $attributes) . ' (' . format_string($course->hostname) . ')', 2, 'title');
                        }
			
			
			
                        $html .= html_writer::end_tag('div'); // course_title (?)
			
			
			
                        //Ajoute de l'espace dans le bloc du cours
                        //$html .= $this->output->box('', 'flush');
			
			
                        ///////////////RECUPERER  AVANCEMENT     ////////////////////////////////
                        // Recuperation du role de l'utilisateur dans le cours ainsi que  les informations concernant les activites du cours
                        $context = context_course::instance($course->id, true); 
						
                        $roles = array();
                        $roles = get_user_roles($context, $USER->id, true); // Retourne le ou les role(s) de l'utilisateur connecté pour le cours courant.
			
                        $completion = new completion_info($course);
                        // Recupere les activites faisant partie du suivi d'achevement
                        $activities = $completion->get_activities();
                        $nb_act = count($activities);

                        $avancementGlobal = "";
                        $avancementDetaille ="";
                        $roleUtilisateur = "";
                        // Si l'utilisateur connecté avecun role dans le cours et qu'il y a au moins une activite dans le suivi d'achevement
                        if($nb_act > 0){
				
                            foreach($roles as $role){ // Recuperation du role qui nous intéresse !
					
                                if(strcmp($role->shortname, "teacher" ) == 0 || strcmp($role->shortname, "editingteacher" ) == 0 ) {
                                    $roleUtilisateur = "teacher";
                                    break;
                                }elseif(strcmp($role->shortname, "student" ) == 0){
                                    $roleUtilisateur = "student";
                                }
					
                            }
                        } 
			
		
                        // Recupere ce qui permet d'afficher le suivi d'avancement selon le role de l'utilisateur dans ce cours
                        if($roleUtilisateur == "teacher" ) {
			
                            // Recupere les deux types d'avancement dans deux variables differentes
                            list($avancementGlobal,$avancementDetaille) = $this->afficherAvtProf($course); 
                            //$html .= $avancementDetaille;
				
                        } elseif($roleUtilisateur == "student"){

                            // Recupere les deux types d'avancement dans deux variables differentes
                            list($avancementGlobal,$avancementDetaille) = $this->afficherAvtEtu($course); 
                            //$html .= $avancementDetaille;
                        }
		
						
			
			
                        //////////////////////////AFFICHER AVANCEMENT GLOBAL ////////////////////////////////////////////
                        //Affiche l'avancement global s'il existe
                        $html.= '<div class="cours-avancement-global">';
                        if($avancementGlobal !== ""){
                            $html .= $avancementGlobal;
                            $html .='<div class="clear"></div>';
                        }
                        $html .= html_writer::end_tag('div'); 
                        //$html .= $this->output->box('', 'flush');
			
			
			
                        ///////////////////////////////////     AFFICHER AVANCEMENT  DETAILLE &  NOTIFICATIONS  ////////////////////////////////
                        $html.= '<div class="cours-infos">';
			
                        // avancement ou si acucune activité : 
                        if($nb_act > 0 ){
                            $html .= $avancementDetaille;
                        }
			

                        // Notification : 

                            if (isset($overviews[$course->id]) && !$ismovingcourse) {
				
                                $html .= $this->afficher_notification($course,$overviews);
                            }
			
                            $html .= html_writer::end_tag('div'); // fin de cours-infos
					
					
                            /////////////////////////// /////////////////////////////////
					
                            // If user is moving courses, then down't show overview. 		
                            // overviews[] -> Contient les notifications
                            // Crée un menu deroulant avec les notifications dedans

			
          
                            //$html .= $this->output->box('', 'flush');

			
                            // Utilite ?
                            if (!empty($config->showchildren) && ($course->id > 0)) {
                                // List children here.
                                if ($children = block_tableau_bord_get_child_shortnames($course->id)) {
                                    $html .= html_writer::tag('span', $children, array('class' => 'coursechildren'));
                                }
                            }
			
			
                            $courseordernumber++;
                            // Permet de deplacer les cours lorsque l'on n'utilise pas le drag and drop
                            if ($ismovingcourse) {
                                $moveurl = new moodle_url('/blocks/tableau_bord/move.php',
                                array('sesskey' => sesskey(), 'moveto' => $courseordernumber, 'courseid' => $movingcourseid));
                                $a = new stdClass();
                                $a->movingcoursename = $courses[$movingcourseid]->fullname;
                                $a->currentcoursename = $course->fullname;
                                $movehereicon = html_writer::empty_tag('img',
                                array('src' => $this->output->image_url('movehere'),
                                'alt' => get_string('moveafterhere', 'block_tableau_bord', $a),
                                'title' => get_string('movehere')));
                                $moveurl = html_writer::link($moveurl, $movehereicon);
                                $html .= html_writer::tag('div', $moveurl, array('class' => 'movehere'));
                            }
			
                            /////////////////////////// Bouton pour acceder au cours////////// ////////// ////////// 
                            $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
                            $html .= '<div class="course-button"><a href="'.$courseurl.'">' .get_string('reach','block_tableau_bord').' </a></div>';
                            $html .='<div class="clear"></div>'; 
			
			
			
                            $html .= $this->output->box_end(); // Fin du coursebox
                        }
                        // Wrap course list in a div and return.
        
                        //$html .= '</div>'; // end div "tabs"
        
                        return html_writer::tag('div', $html, array('class' => 'course_list', 'id' => 'test'));
                    }

                    /**
                    * Constructs header in editing mode
                    *
                    * @param int $max maximum number of courses
                    * @return string html of header bar.
                    */
                    public function editing_bar_head($max = 0) {
                        $output = $this->output->box_start('notice');

                        $options = array('0' => get_string('alwaysshowall', 'block_tableau_bord'));
                        for ($i = 1; $i <= $max; $i++) {
                            $options[$i] = $i;
                        }
                        $url = new moodle_url('/my/index.php');
                        $select = new single_select($url, 'mynumber', $options, block_tableau_bord_get_max_user_courses(), array());
                        $select->set_label(get_string('numtodisplay', 'block_tableau_bord'));
                        $output .= $this->output->render($select);

                        $output .= $this->output->box_end();
                        return $output;
                    }

                    /**
                    * Show hidden courses count
                    *
                    * @param int $total count of hidden courses
                    * @return string html
                    */
                    public function hidden_courses($total) {
                        if ($total <= 0) {
                            return;
                        }
                        $output = $this->output->box_start('notice');
                        $plural = $total > 1 ? 'plural' : '';
                        $output .= get_string('hiddencoursecount'.$plural, 'block_tableau_bord', $total);
                        $output .= $this->output->box_end();
                        return $output;
                    }

                    /**
                    * Creates collapsable region
                    *
                    * @param string $contents existing contents
                    * @param string $classes class names added to the div that is output.
                    * @param string $id id added to the div that is output. Must not be blank.
                    * @param string $caption text displayed at the top. Clicking on this will cause the region to expand or contract.
                    * @param string $userpref the name of the user preference that stores the user's preferred default state.
                    *      (May be blank if you do not wish the state to be persisted.
                    * @param bool $default Initial collapsed state to use if the user_preference it not set.
                    * @return bool if true, return the HTML as a string, rather than printing it.
                    */
                    protected function collapsible_region($contents, $classes, $id, $caption, $userpref = '', $default = false) {
                        $output  = $this->collapsible_region_start($classes, $id, $caption, $userpref, $default);
                        $output .= $contents;
                        $output .= $this->collapsible_region_end();

                        return $output;
                    }

                    /**
                    * Print (or return) the start of a collapsible region, that has a caption that can
                    * be clicked to expand or collapse the region. If JavaScript is off, then the region
                    * will always be expanded.
                    *
                    * @param string $classes class names added to the div that is output.
                    * @param string $id id added to the div that is output. Must not be blank.
                    * @param string $caption text displayed at the top. Clicking on this will cause the region to expand or contract.
                    * @param string $userpref the name of the user preference that stores the user's preferred default state.
                    *      (May be blank if you do not wish the state to be persisted.
                    * @param bool $default Initial collapsed state to use if the user_preference it not set.
                    * @return bool if true, return the HTML as a string, rather than printing it.
                    */
                    protected function collapsible_region_start($classes, $id, $caption, $userpref = '', $default = false) {
                        // Work out the initial state.
                        if (!empty($userpref) and is_string($userpref)) {
                            user_preference_allow_ajax_update($userpref, PARAM_BOOL);
                            $collapsed = get_user_preferences($userpref, $default);
                        } else {
                            $collapsed = $default;
                            $userpref = false;
                        }

                        if ($collapsed) {
                            $classes .= ' collapsed';
                        }

                        $output = '';
                        $output .= '<div id="' . $id . '" class="collapsibleregion ' . $classes . '">';
                        $output .= '<div id="' . $id . '_sizer">';
                        $output .= '<div id="' . $id . '_caption" class="collapsibleregioncaption">';
                        $output .= $caption . ' ';
                        $output .= '</div><div id="' . $id . '_inner" class="collapsibleregioninner">';
                        $this->page->requires->js_init_call('M.block_tableau_bord.collapsible', array($id, $userpref, get_string('clicktohideshow')));

                        return $output;
                    }

                    /**
                    * Close a region started with print_collapsible_region_start.
                    *
                    * @return string return the HTML as a string, rather than printing it.
                    */
                    protected function collapsible_region_end() {
                        $output = '</div></div></div>';
                        return $output;
                    }

                    /**
                    * Cretes html for welcome area
                    *
                    * @param int $msgcount number of messages
                    * @return string html string for welcome area.
                    */
                    public function welcome_area($msgcount) {
                        global $USER;
                        $output = $this->output->box_start('welcome_area');

                        $picture = $this->output->user_picture($USER, array('size' => 25, 'class' => 'welcome_userpicture'));
                        $output .= html_writer::tag('div', $picture, array('class' => 'profilepicture'));

                        $output .= $this->output->box_start('welcome_message');
                        $output .= $this->output->heading(get_string('welcome', 'block_tableau_bord', $USER->firstname));

                        $output .= '( ';
                        $plural = '';
                        if ($msgcount > 0) {
                            $output .= get_string('youhavemessages', 'block_tableau_bord', $msgcount);
                            if ($msgcount > 1) {
                                $plural = 's';
                            }
                        } else {
                            $output .= get_string('youhavenomessages', 'block_tableau_bord');
           
                        }
                        $output .= html_writer::link(new moodle_url('/message/index.php'), get_string('message'.$plural, 'block_tableau_bord'));
                        $output .= ' )';
                        $output .= $this->output->box_end();
		
                        $output .= $this->output->box_end();

                        $output .= '<div class="welcome_title"><p>';
                        $output .= get_string('plugintitle', 'block_tableau_bord') ;
                        $output .= '</p></div>';
		
                        return $output;
                    }
	
	
	
	
                    ////////////////////////////////////////   NOUVELLES FONCTIONS  ///////////////////////////////////////////////////////////////
	
	
	
	
                    public function afficherAvtProf($course){
                        global $OUTPUT;
                        $avancement_global = "";
                        $avancement_detaille ="";
                        ///////////////// Recuperation des donnees du cours (code de la page rapport d'activite) ////////////////////
		
                        $context = context_course::instance($course->id);

                        // Sort (default lastname, optionally firstname)
                        $sort = optional_param('sort','',PARAM_ALPHA);
		
                        $start   = optional_param('start', 0, PARAM_INT);
		
                        // Get group mode
                        $group = groups_get_course_group($course,true); // Supposed to verify group
                        if ($group===0 && $course->groupmode==SEPARATEGROUPS) {
                            require_capability('moodle/site:accessallgroups',$context);
                        }
		
		
                        $completion = new completion_info($course);
                        $activities = $completion->get_activities(); // Activites faisant partie du suivi d'achevement
		
                        // Generate where clause
                        $where = array();
                        $where_params = array();
		
                        // Get user match count
                        $total = $completion->get_num_tracked_users(implode(' AND ', $where), $where_params, $group);
		
                        // Get user data
                        $progress = array();
		
                        if ($total) {
                            $progress = $completion->get_progress_all(
                            implode(' AND ', $where),
                            $where_params,
                            $group,
                            $sort,
                            $total,
                            $start,
                            $context
                        );
                    }
                    ////////////////////////     Fin recuperation donnees     /////////////////////////////////////
		
		
                    $nb_act_achevee_total=0; /* nombre activite achevees par tous les etudiants */
                    $nb_act_non_achevee_total=0; /* nombre activite non achevees par tous les etudiants */
		
		
                    $tableau_histo = array(0,0,0,0,0,0,0,0,0,0,0); // Tableau pour construire l'histogramme
		
                    // Pour chaque utilisateur
                    foreach($progress as $user) {
                        $nb_act_achevee = 0; // Nombre d'activite achevees par l'utilisateur
                        $nb_act_non_achevee = 0; // Nombre d'activite non achevees par l'utilisateur
                        $pourcentage_achevement = 0;
			
                        // Pour chaque activite faisant partie du suivi d'achevement
                        foreach($activities as $activity) {
		
                            // Recuperation de l'etat de l'activite
                            if (array_key_exists($activity->id,$user->progress)) {
                                $thisprogress = $user->progress[$activity->id];
                                $state = $thisprogress->completionstate;
                                //$date=userdate($thisprogress->timemodified);
                            } else {
                                $state=COMPLETION_INCOMPLETE;
                                //$date='';
                            }
		
                            // Incremente les variables en fonction de l'etat de l'activite
                            switch($state) {
                                case COMPLETION_INCOMPLETE : $nb_act_non_achevee++;$nb_act_non_achevee_total++; break;
                                case COMPLETION_COMPLETE : $nb_act_achevee++;$nb_act_achevee_total++; break;
                                case COMPLETION_COMPLETE_PASS : $nb_act_achevee++;$nb_act_achevee_total++;break;
                                case COMPLETION_COMPLETE_FAIL : $nb_act_non_achevee++;$nb_act_non_achevee_total++; break;
                            }	
                        }
			
                        $pourcentage_achevement = $nb_act_achevee/count($activities) * 100; // Pourcentage d'activite completees		
                        $palier = floor($pourcentage_achevement/10); // Recupere l'indice correspondant a un palier d'avancement 		
                        $tableau_histo[$palier]++; // Incremente le nombre d'etudiant dans le palier correspondant
			
                    }
		
                    // Pourcentage de l'avancement moyen de tous les utilisateurs
                    $pourcentage_acheve = ($nb_act_achevee_total / ($nb_act_non_achevee_total + $nb_act_achevee_total)) * 100;
                    $pourcentage_non_acheve = ($nb_act_non_achevee_total / ($nb_act_non_achevee_total + $nb_act_achevee_total)) * 100;
								
                    $id_canvas_histo = "canvasHisto".$course->id;
                    $id_canvas_global = "affichage-global-cours-".$course->id;
 
                    $histogramme =""; // Contient le code html permettant d'afficher l'histogramme
		
                    // Creation du canvas pour l'histo et des variable JS a passer en parametre de la fonction JS qui creera l'histo
                    $histogramme .=
                        '<canvas id="'.$id_canvas_histo.'"  width="450"></canvas>'.
					
                            '<script type="text/javascript">
                                var canvasHisto = "'.$id_canvas_histo.'";
                    var tableauHisto = new Array();'
                    ;		   
                    for ($i=0;$i<count($tableau_histo);$i++) { // Rempli le tableau javascript a l'aide du tableau php
                        $histogramme .= "tableauHisto[".$i."] = ".$tableau_histo[$i].";";
                    }
		
                    // lien vers le rapport "achèvement d'activités" dans le cours
                    $url = new moodle_url("/report/tuteur/index.php", array('course' => $course->id));
		
                    // Creation de l'histogramme pour le cours
                    $histogramme .= 'creerBar(canvasHisto,tableauHisto); </script>
                        <center><b>Nombre d\'étudiants par pourcentage d\'avancement</b>'
                    .$OUTPUT->help_icon('teacherdetailedprogress','block_tableau_bord')
                        .'</center>'
                            .'<div class = "link-button"> <a href="'.$url.'">Afficher l\'achèvement d\'activités par étudiant </a> </div>';
                    // Creation de l'affichage global pour le cours (camembert)
                    $avancement_global .=
                        '<center><canvas id="'.$id_canvas_global.'" height="90" width="120"></canvas></center>
                            <script>
                    var canvasGlobal="affichage-global-cours-'.$course->id.'";
                    var pourcentage_act_complet_'.$course->id.' ='.$pourcentage_acheve.';
                    var pourcentage_act_incomplet_'.$course->id.' ='.$pourcentage_non_acheve.';
                    creerPieProf(canvasGlobal, pourcentage_act_complet_'.$course->id.', pourcentage_act_incomplet_'.$course->id.');
                    </script>
                    <center><b>'. get_string('teacherprogress','block_tableau_bord').' : '.intval($pourcentage_acheve).' % </b> '.$OUTPUT->help_icon('teacherglobalprogress','block_tableau_bord').'</center>	';

                    // Contient l'histogramme dans une menu deroulant
                    $avancement_detaille .= 
                        $this->collapsible_region($histogramme, '', 'region_histo_'.$course->id, 
                    '<img src="'. $OUTPUT->image_url('avancement_18', 'block_tableau_bord').'" alt="Avancement icon" /> '.
                        '<b>'.get_string('seeprogressteacher','block_tableau_bord').'</b>', '', true);
                    // Retourne les deux types d'avancement separement
                    return array($avancement_global,$avancement_detaille);
		
                }
	
	
                public function afficherAvtEtu($course) {
                    global $OUTPUT;
                    $avancement_global =""; // Contient le code html affichant l'avancement global
                    $avancement_detaille =""; // Contient le code html affichant l'avancement detaille
		
                    $completion = new completion_info($course);
		
                    // TOUTES les infos de toutes les activites faisant partie du suivi d'achevement 
                    $activities = $completion->get_activities();
		
                    $nb_act = count($activities); // Nombre total d'activites
		
                    $context = context_course::instance($course->id);
		
                    $nb_act_achevee = 0; // Nombre total d'activites achevees
                    $nb_act_non_achevee = 0; // Nombre total d'activites non achevees
		
                    // Nombre d'activites achevees et non achevees pour differents types d'activites
                    $devoir_acheve = 0;
                    $devoir_non_acheve =0;
                    $lecon_achevee = 0;
                    $lecon_non_achevee =0;
                    $ressource_achevee = 0;
                    $ressource_non_achevee =0;
                    $test_acheve = 0;
                    $test_non_acheve =0;
                    $autre_acheve = 0;
                    $autre_non_acheve =0;
		
                    // Compte le nombre total d'activites achevees et non achevees pour differents types d'activite ( la table "mdl_modules" liste les types)
                    foreach($activities as $activity) {
                        $data = $completion->get_data($activity);
                        // Si l'activite est achevee
                        if ($data->completionstate) {
                            $nb_act_achevee = $nb_act_achevee + 1;
                            if($activity->modname == 'assign'){
                                $devoir_acheve = $devoir_acheve + 1;
                            } elseif($activity->modname == 'lesson'){
                                $lecon_achevee = $lecon_achevee + 1;
                            } elseif($activity->modname == 'resource'){
                                $ressource_achevee = $ressource_achevee + 1;
                            } elseif($activity->modname == 'quiz'){
                                $test_acheve = $test_acheve + 1;
                            }else{
                                $autre_acheve = $autre_acheve + 1;
                            }
                        } else {
                            $nb_act_non_achevee++;
                            if($activity->modname == 'assign'){
                                $devoir_non_acheve = $devoir_non_acheve + 1;
                            } elseif($activity->modname == 'lesson'){
                                $lecon_non_achevee = $lecon_non_achevee + 1;
                            } elseif($activity->modname == 'resource'){
                                $ressource_non_achevee = $ressource_non_achevee + 1;
                            } elseif($activity->modname == 'quiz'){
                                $test_non_acheve = $test_non_acheve + 1;
                            }else{
                                $autre_non_acheve = $autre_non_acheve + 1;
                            }
                        }
                    }
		
	
                    $pourcentage_achevee = $nb_act_achevee/$nb_act*100; // Pourcentage d'activites achevees
                    $pourcentage_non_achevee = 100 - $pourcentage_achevee; // Pourcentage d'activites non achevees
		
                    $avancement_activite =""; // Contient l'affichage detaille (un graphe pour un type d'activite)
		
                    // Pourcentage de completion pour chaque type d'activite ainsi qu'ajout du canvas et du graphe dans celui-ci
                    if($devoir_acheve + $devoir_non_acheve > 0){
                        $pourcentage_devoir_acheve = $devoir_acheve / ($devoir_acheve + $devoir_non_acheve) * 100;
                        $pourcentage_devoir_non_acheve = 100 - $pourcentage_devoir_acheve;
                        $avancement_activite .=  '<div class = canvasDetaille><center><b>'. get_string('assign','block_tableau_bord') .'</b></center>
                            <canvas id="affichage-devoir-cours-'.$course->id.'" height="60" width="80"></canvas>';
			
                        // Affiche le nombre de devoirs restants
                        if($devoir_non_acheve <= 1){
                            $avancement_activite .= '<center>'. get_string('oneactivityremaining','block_tableau_bord',$devoir_non_acheve) .'</center></div>';
                        } else {
                            $avancement_activite .= '<center>'. get_string('someactivityremaining','block_tableau_bord',$devoir_non_acheve) .'</center></div>';
                        }
			
                        $avancement_activite .=	'<script>
                            var canvas_devoir = "affichage-devoir-cours-'.$course->id.'";
										
                        var pourcentage_devoir_acheve_'.$course->id.' ='.$pourcentage_devoir_acheve.';
                        var pourcentage_devoir_non_acheve_'.$course->id.' ='.$pourcentage_devoir_non_acheve.';
										
                        creerPieDetaille(canvas_devoir, pourcentage_devoir_acheve_'.$course->id.', pourcentage_devoir_non_acheve_'.$course->id.');
                        </script>';
                    }
		
                    if($lecon_achevee + $lecon_non_achevee > 0){
                        $pourcentage_lecon_achevee = $lecon_achevee / ($lecon_achevee + $lecon_non_achevee) * 100;
                        $pourcentage_lecon_non_achevee = 100 - $pourcentage_lecon_achevee;
                        $avancement_activite .=	'<div class = canvasDetaille><center><b>'. get_string('lesson','block_tableau_bord') .'</b></center>
                            <canvas id="affichage-lecon-cours-'.$course->id.'" height="60" width="80"></canvas>';

                        // Affiche le nombre de lecons restantes
                        if($lecon_non_achevee <= 1){
                            $avancement_activite .= '<center>'. get_string('oneactivityfeminineremaining','block_tableau_bord',$lecon_non_achevee) .'</center></div>';
                        } else {
                            $avancement_activite .= '<center>'. get_string('someactivityfeminineremaining','block_tableau_bord',$lecon_non_achevee) .'</center></div>';
                        }
			
                        $avancement_activite .=	'<script>
                            var canvas_lecon = "affichage-lecon-cours-'.$course->id.'";
										
                        var pourcentage_lecon_achevee_'.$course->id.' ='.$pourcentage_lecon_achevee.';
                        var pourcentage_lecon_non_achevee_'.$course->id.' ='.$pourcentage_lecon_non_achevee.';
										
                        creerPieDetaille(canvas_lecon, pourcentage_lecon_achevee_'.$course->id.', pourcentage_lecon_non_achevee_'.$course->id.');
                        </script>';
                    }
		
                    if($ressource_achevee + $ressource_non_achevee > 0){
                        $pourcentage_ressource_achevee = $ressource_achevee / ($ressource_achevee + $ressource_non_achevee) * 100;
                        $pourcentage_ressource_non_achevee = 100 - $pourcentage_ressource_achevee;
                        $avancement_activite .=	'<div class = canvasDetaille><center><b>'. get_string('resource','block_tableau_bord') .'</b></center>
                            <canvas id="affichage-ressource-cours-'.$course->id.'" height="60" width="80"></canvas>';
			
                        // Affiche le nombre de ressources restantes
                        if($ressource_non_achevee <= 1){
                            $avancement_activite .= '<center>'. get_string('oneactivityfeminineremaining','block_tableau_bord',$ressource_non_achevee) .'</center></div>';
                        } else {
                            $avancement_activite .= '<center>'. get_string('someactivityfeminineremaining','block_tableau_bord',$ressource_non_achevee) .'</center></div>';
                        }
			
                        $avancement_activite .= '<script>
                            var canvas_ressource = "affichage-ressource-cours-'.$course->id.'";
										
                        var pourcentage_ressource_achevee_'.$course->id.' ='.$pourcentage_ressource_achevee.';
                        var pourcentage_ressource_non_achevee_'.$course->id.' ='.$pourcentage_ressource_non_achevee.';
										
                        creerPieDetaille(canvas_ressource, pourcentage_ressource_achevee_'.$course->id.', pourcentage_ressource_non_achevee_'.$course->id.');
                        </script>';
                    }
		
                    if($test_acheve + $test_non_acheve > 0){
                        $pourcentage_test_acheve = $test_acheve / ($test_acheve + $test_non_acheve) * 100;
                        $pourcentage_test_non_acheve = 100 - $pourcentage_test_acheve;
                        $avancement_activite .=	'<div class = canvasDetaille><center><b>'. get_string('quiz','block_tableau_bord') .'</b></center>
                            <canvas id="affichage-test-cours-'.$course->id.'" height="60" width="80"></canvas>';
			
                        // Affiche le nombre de ressources restantes
                        if($test_non_acheve <= 1){
                            $avancement_activite .= '<center>'. get_string('oneactivityremaining','block_tableau_bord',$test_non_acheve) .'</center></div>';
                        } else {
                            $avancement_activite .= '<center>'. get_string('someactivityremaining','block_tableau_bord',$test_non_acheve) .'</center></div>';
                        }
			
                        $avancement_activite .= '<script>
                            var canvas_test = "affichage-test-cours-'.$course->id.'";
										
                        var pourcentage_test_acheve_'.$course->id.' ='.$pourcentage_test_acheve.';
                        var pourcentage_test_non_acheve_'.$course->id.' ='.$pourcentage_test_non_acheve.';
										
                        creerPieDetaille(canvas_test, pourcentage_test_acheve_'.$course->id.', pourcentage_test_non_acheve_'.$course->id.');
                        </script>';
                    }
		
                    if($autre_acheve + $autre_non_acheve > 0){
                        $pourcentage_autre_acheve = $autre_acheve / ($autre_acheve + $autre_non_acheve) * 100;
                        $pourcentage_autre_non_acheve = 100 - $pourcentage_autre_acheve;
                        $avancement_activite .=	'<div class = canvasDetaille><center><b>'. get_string('otheractivity','block_tableau_bord') .'</b></center>
                            <canvas id="affichage-autre-cours-'.$course->id.'" height="60" width="80"></canvas>';
				
                        // Affiche le nombre d'autres activites restantes
                        if($autre_non_acheve <= 1){
                            $avancement_activite .= '<center>'. get_string('oneactivityfeminineremaining','block_tableau_bord',$autre_non_acheve) .'</center></div>';
                        } else {
                            $avancement_activite .= '<center>'. get_string('someactivityfeminineremaining','block_tableau_bord',$autre_non_acheve) .'</center></div>';
                        }
			
                        $avancement_activite .=	'<script>
                            var canvas_autre = "affichage-autre-cours-'.$course->id.'";
										
                        var pourcentage_autre_acheve_'.$course->id.' ='.$pourcentage_autre_acheve.';
                        var pourcentage_autre_non_acheve_'.$course->id.' ='.$pourcentage_autre_non_acheve.';
										
                        creerPieDetaille(canvas_autre, pourcentage_autre_acheve_'.$course->id.', pourcentage_autre_non_acheve_'.$course->id.');
                        </script>';
                    }
                    // info bulle à la fin : 
                    $avancement_activite .= $OUTPUT->help_icon('studentdetailedprogress','block_tableau_bord');

                    // Cree le diagramme d'avancement global
                    $avancement_global .= '	<center><canvas id="affichage-global-cours-'.$course->id.'" height="90" width="120" ></canvas></center>
                        <script>
                    var canvas = "affichage-global-cours-'.$course->id.'";
                    var pourcentage_act_achevee_'.$course->id.' ='.$pourcentage_achevee.';
                    var pourcentage_act_non_achevee_'.$course->id.' ='.$pourcentage_non_achevee.';
                    creerPie(canvas, pourcentage_act_achevee_'.$course->id.', pourcentage_act_non_achevee_'.$course->id.');
                    </script>
					
                    <center><b>'. get_string('studentprogress','block_tableau_bord').' : '.intval($pourcentage_achevee).' %</b>
                    '.$OUTPUT->help_icon('studentglobalprogress','block_tableau_bord').'</center>';
				
	
                    // Contient les diagrammes d'avancement des types d'activite dans un menu deroulant
                    $avancement_detaille .= $this->collapsible_region($avancement_activite, '', 'region_detaille_'.$course->id,
                    '<img src="'. $OUTPUT->image_url('avancement_18', 'block_tableau_bord').'" alt="Avancement icon" /> '.
                        '<b>'.get_string('seeprogress','block_tableau_bord').'</b>', '', true);
		
                    // Renvoie les deux types d'avancement separement
                    return array($avancement_global,$avancement_detaille);
                }
	
                public function menu($courses){
                    global $USER,$CFG;
                    // div pour le menu
                    $html ='<div class="menu">';
                    $html .= '<div class="welcome_title"><p>';
                    $html .= get_string('plugintitle', 'block_tableau_bord') ;
                    $html .= '</p></div>';
		
		
                    // affiche la welcome area (nom user + messages non lu)
                    /*require_once($CFG->dirroot.'/message/lib.php');
                    $msgcount = message_count_unread_messages();
                    $html .= $this->welcome_area($msgcount);
                    */
		
		
		
                    // Tri les cours par ordre alphabetique du titre
                    /*$html .= '<button id="testTriV">Trier</button>';
                    $html .= '<script>
                    $(document).ready(function(){
                    //click sur bouton testTriV
                    $("#testTriV").bind("click",function(){
                    $(".coursebox").sort(function(a,b){
                    var c = a.getElementsByClassName("cours-titre");
                    var d = b.getElementsByClassName("cours-titre");
                    //alert($(c).text());
                    //alert($(d).text());

                    return $(c).text() > $(d).text() ? 1 : -1;
                    }).remove().appendTo("#test");
                    });
                    });
                    </script>';*/

		
		
                    // Liste déroulante pour le choix de l'ordre des cours en JS
                    /*$html .='<div class = "formulaire">
                    <form id = "ordre_cours" method="post" action="../blocks/tableau_bord/ordre_cours.php" onChange = document.forms["ordre_cours"].submit()>
                    <p>
                    <select name="ordre_cours" >
                    <option value="test" selected="selected" disabled>Choisir l\'ordre des cours</option>
                    <option value = "alpha">Ordre Alphabétique</option>
                    <option value= "pref">Ordre Préférentiel</option>			  
                    </select>		
                    </p>
                    </form></div>';
                    */
		
                    // $userediting : booleen vrai si l'utilisateur est en cours de modification de l'ordre des cours, faux sinon
                    $userediting = !empty($USER->userediting_course) && $USER->userediting_course == true;
		
		
                    $html .='<div class = "formulaire">';
                    // Si en mode edition, on affiche le bouton pour activer le drag and drop
                    // Sinon bouton pour valider le choix une fois que l'utilisateur a reorganise ses cours
                    if($userediting){
                        $html .='
                            <form method="post" action="'. $CFG->wwwroot.'/blocks/tableau_bord/ordre_cours.php'.'">
                        <p>
                        <input type="submit" value="'. get_string('validorder', 'block_tableau_bord') .'" title="validerCours" name="valider" />
                        </p>
                        </form>';
                    } else {
                        $html .= '<form id = "ordre_cours" method="post" action="'.$CFG->wwwroot.'/blocks/tableau_bord/ordre_cours.php'.'">
                            <p>
                        <input class="modifier_ordre_cours" type="submit" value="'. get_string('changeorder', 'block_tableau_bord') .'" name="ordre_cours" />
                        </p>
                        </form>';
                    }
                    $html .='</div>';
		
                    // Construit un tableau avec les id de chaque cours
                    /*$tableau_cours = array();
                    $i = 0;
                    foreach ($courses as $key => $course) {
                    $tableau_cours[$i] = $course->id;
                    $i++;
                    }*/
		
                    ////////////////////////// BOUTONS AFFICHAGE //////////////////////////////
		
                    // Boutons pour changer l'affichage avec du JS
                    // Modifie dynamiquement les classes des cours
                    // cree/modifie le cookie qui sauvegarde la derniere classe utilisee pour les cours
                    /*		$html .='<script>
                    var tableau_cours = new Array();';
                    // Rempli le tableau JS avec le tableau PHP contenant les id des cours		 
                    for ($i=0;$i<count($tableau_cours);$i++) {
                    $html .= 'tableau_cours['.$i.'] = "course-'.$tableau_cours[$i].'";';			
                    }
		
                    */		
                    /* setCookie : methode de mise a jour/creation du cookie
                    * afficherGrille : methode modifiant la classe des cours en coursebox_grille
                    * afficherListe : methode modifiant la classe des cours en coursebox
                    * Bouton executant ces fonctions */
                    /*		$html .='	document.setCookie = function(sName, sValue) {
                    var aujourdhui = new Date();
                    var fin = new Date();
                    fin.setTime(aujourdhui.getTime() + (365*24*60*60*1000));
                    document.cookie = sName + "=" + encodeURIComponent(sValue) + ";fin=" + fin.toGMTString();
                    }
                    document.afficherGrille = function(){
                    for(i=0;i<tableau_cours.length;i++){
                    document.getElementById(tableau_cours[i]).className="coursebox_grille";
                    }						 
                    }
                    document.afficherListe = function(){
                    for(i=0;i<tableau_cours.length;i++){
                    document.getElementById(tableau_cours[i]).className="coursebox";
                    }						 
                    }
                    </script>
                    <div class = "bouton">
                    <p><b>' .get_string('changedisplay','block_tableau_bord'). '</b></p>
                    <div class = "bouton"><a href=\'javascript:document.setCookie("disposition","coursebox");document.afficherListe();\'><img src="'.$CFG->wwwroot.'/blocks/tableau_bord/liste.png'.'" width="40" height="40"></a></div>
                    <div class = "bouton"><a href=\'javascript:document.setCookie("disposition","coursebox_grille");document.afficherGrille();\'><img src="'.$CFG->wwwroot.'/blocks/tableau_bord/grille.jpg'.'" width="40" height="40"></a></div>
                    </div>';
                    */		/*		
                    // Bouton pour choisir l'affichage en camembert
                    $html .= '<div class = bouton_aff>';
                    $html .= '<script>
                    document.camembert = function(){';
                    // Boucle PHP qui rempli la fonction document.camembert du JS car besoin des $id des cours
                    // Les variables pourcentage_act_achevee_idcours et pourcentage_act_non_achevee_idcours sont creees dans la fonction AfficherAvtEtu()
                    foreach($courses as $course){
                    $completion = new completion_info($course);
                    $activities = $completion->get_activities();
                    $nb_act = count($activities);
                    if($nb_act>0){
                    $html .= 'creerPie("affichage-global-cours-'.$course->id.'",pourcentage_act_achevee_'.$course->id.',pourcentage_act_non_achevee_'.$course->id.');';
                    }
                    }
                    // Fin de creation du bouton
                    $html .= '}
                    </script><p><b>Choix Graphe</b></p>
                    <div class = "bouton"><a href="javascript:document.camembert();"><img src="../blocks/tableau_bord/pie.png" width="40" height="40"></a></div>';
	
		
                    // Bouton pour choisir l'affichage en doughnut
                    $html .= '<script>
                    document.doughnut = function(){';
                    // Boucle PHP qui rempli la fonction document.doughnut du JS car besoin des $id des cours
                    foreach($courses as $course){
                    $completion = new completion_info($course);
                    $activities = $completion->get_activities();
                    $nb_act = count($activities);
                    if($nb_act>0){
                    $html .= 'creerDoughnut("affichage-global-cours-'.$course->id.'",pourcentage_act_achevee_'.$course->id.',pourcentage_act_non_achevee_'.$course->id.');';
                    }
                    }
                    // Fin de creation du bouton
                    $html .= '}
                    </script>
                    <div class = "bouton"><a href="javascript:document.doughnut();"><img src="../blocks/tableau_bord/doughnut.png" width="40" height="40"></a></div></div>';
                    */
		
                    // Fin de la div du menu du haut
                    $html .= $this->output->box('', 'flush');
                    $html .= '</div>';
		
                    return $html;
                }
	
	
                // Fonction qui cree et retourne le menu deroulant avec toutes les notifications pour un seul cours ainsi que leur bouton de suppression
                // Retourne une variable au format html
                public function afficher_notification($course,$overviews){
                    global $USER, $DB, $OUTPUT, $CFG;
		
                    $notif_activite = "";          // Contient toutes les notifications d'un type d'activite		
                    $notif_activite_complete = ""; // Contient toutes les notifications d'un type d'activite plus le titre		
                    $html = '';                // Contient toutes les notifications dans un menu deroulant		
                    $nb_type_activite = 0;         // Nombre de types d'activite different a afficher : correspond au nombre de notifications

		
                    // Pour chaque type d'activite du cours
                    foreach (array_keys($overviews[$course->id]) as $module) {
			
                        $nb_activite_affichee = 0; // Nb activite qui sont affichees : au depart 0			
                        $presence_notif = false;   // Booleen qui indique la presence de notification(s) affichee(s) ou non
			
                        // Pour chaque notification de ce type d'activite
                        foreach($overviews[$course->id][$module] as $coursemodule=>$notif){
                            $param_notif = array('id_user' => $USER->id,'id_course_module' => $coursemodule );			
                            $presence_notif = true; // Presence de notification a afficher
				
                            // Lors du clic sur le bouton de suppression, un formulaire est appelé en JS/ajax pour s'executer sans ouvrir de page
                            // Si cette notification est la derniere de son type d'activite dans un cours, on supprime le titre du type de notification
                            $notif_activite .='<script>
                            document.effacerNotif'.$coursemodule.' = function(){
                                document.getElementById("'.$coursemodule.'").className="hidden";
                                document.getElementById("bouton'.$coursemodule.'").className="hidden";
										
                                compteur_notif_'.$module.'_course_'.($course->id).' -= 1;
                                if(compteur_notif_'.$module.'_course_'.($course->id).' == 0){
                                    document.getElementById("notif-'.$module.'-course-'.($course->id).'").className="hidden";
                                    nombre_type_activite_affiches_'.($course->id).' -= 1;
                                    if(nombre_type_activite_affiches_'.($course->id).' == 0){
                                        document.getElementById("region_notification_'.($course->id).'").className="hidden";
                                    }
                                }
                                // Appelle un formulaire php qui permet d\'ajouter a la table la notification lors du clic sur le bouton de suppression
                                xhr.open("POST", "'.$CFG->wwwroot.'/blocks/tableau_bord/suppression_notif.php'.'", true);
                                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                                xhr.send("id_activite='.$coursemodule.'&id_user='.($USER->id).'");	
                            }
                            </script>';
						 
                            // Creation de la division qui contient la notification en elle meme ainsi que le bouton de suppression
                            $notif_activite .= '<div id='.$coursemodule.' class="notif">';
                            $notif_activite .= $notif; // Contenu de la notification
                            // Bouton de suppression de la notification
                            $notif_activite .= '<a id="bouton'.$coursemodule.'" class="boutonSupprimer" title="Cliquer ici pour supprimer la notification" href=\'javascript:document.effacerNotif'.$coursemodule.'();\'>
                                <div class="delete_notif">X</div>
                            </a>';//<img src="'.$CFG->wwwroot.'/blocks/tableau_bord/boutonSupprimer.png'.'" width="20" height="20">
				
                            $notif_activite .= '</div>';
                            $notif_activite .= '<div class="clear"></div>';
				
                            $nb_activite_affichee ++; // Le nombre d'activite qui sont effectivement affichees
                        }
                        // Cree une variable JS contenant le nombre de notifs affichees pour un type d'activite donne dans un cours
                        // Quand elle sera a zero elle permettra d'effacer le titre de ces notifs (ex: "vous avez des devoirs...")
                        $notif_activite .= '<script> var compteur_notif_'.$module.'_course_'.($course->id).' = '.$nb_activite_affichee.';</script>';
						
                        $titre_notif_type_activite = ""; // Contient le code html qui permet d'afficher le titre du type des notifs
			
                        // S'il y a une notif de ce type on incremente le nombre de types differents de notifs
                        // Et on construit le titre de ce(s) notif(s)
                        if($presence_notif){
                            $nb_type_activite++;
                            // Recuperation du titre de la notif pour un type d'activite ainsi que l'image correspondant
                            $url = new moodle_url("/mod/$module/index.php", array('id' => $course->id));
				
                            $type_activite = get_string('modulename'.$module, 'block_tableau_bord');
				
                            // Construction du titre pour un type de notification
                            $titre_notif_type_activite .= '<div id="notif-'.$module.'-course-'.($course->id).'">';
                            $titre_notif_type_activite .= html_writer::link($url, $this->output->pix_icon('icon', $type_activite, 'mod_'.$module, array('class'=>'iconlarge')));
                            $titre_notif_type_activite .= '<b>';				
                            $titre_notif_type_activite .= get_string("activityoverview".$module, "block_tableau_bord");				 
                            $titre_notif_type_activite .= '</b></div>';
                        }
			
                        // On concatene les notifs d'un meme type d'activite avec le titre correspondant
                        $notif_activite_complete .= $titre_notif_type_activite.$notif_activite;
                        $notif_activite ="";
                    }
		
                    // Initialisation de la variable contenant le nombre de type d'activite effectivement affiche
                    // Permet de masquer le menu deroulant lorsque toutes les notifs de chaque type d'activites ont ete supprimees
                    $html .= '<script> var nombre_type_activite_affiches_'.($course->id).' = '.$nb_type_activite.';</script>';
		
                    // Construit la phrase indiquant la presence de notification
                    if($nb_type_activite > 0){
                        if($nb_type_activite == 1){
                            $icontext = 
                                '<img src="'. $OUTPUT->image_url('notification_18', 'block_tableau_bord').'" alt="Notification icon" /> '
                                    .'<b>'.get_string('youhaveanotification','block_tableau_bord').'</b>';
                        } else {
                            $icontext = 
                                $icontext = '<img src="'. $OUTPUT->image_url('notification_18', 'block_tableau_bord').'" alt="Notification icon" /> '
                                    .'<b>'.get_string('youhavesomenotifications','block_tableau_bord',$nb_type_activite).'</b>';
                        }
			
			

			
			
                        // Met la variable contenant toutes les notifications ainsi qu'un bouton d'aide dans un menu deroulant
                        $html .= $this->collapsible_region($notif_activite_complete.$OUTPUT->help_icon('notifications','block_tableau_bord'), '', 'region_notification_'.$course->id,$icontext, '', true);

                    }			
                    return $html;
                }
	
                /*public function gestionnaire_image(){
                    global $CFG, $USER;
                    // GESTIONNAIRE
                    $mform = new gestionnaire_image(null);
		
                    // Si un bouton annuler a ete clique
                    if ($mform->is_cancelled()) {
                    echo "1";
                    } else if (($formdata = $mform->get_data()) && confirm_sesskey()) { //Si un bouton valider a ete clique et qu'il renvoie bien un/des fichier(s)
                    require_once($CFG->dirroot . '/lib/uploadlib.php');
                    $fs = get_file_storage();// Recupere les informations pour le stockage de fichiers
                    $draftid = file_get_submitted_draft_itemid('image');// parametre = nom du formulaire. Recupere l'itemid du gestionnaire
                    if (!$files = $fs->get_area_files( 
                    get_context_instance(CONTEXT_USER, $USER->id)->id, 'user', 'draft', $draftid, 'id DESC', false)) {
                    //Si il n'y a pas de fichier dans le gestionnaire renvoyer vers une page ?
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
				
                    // Recuperation + affichage de l'image
                    // $moodleurl = new moodle_url(null);
                    // $url = $moodleurl->make_pluginfile_url($stored_file->get_contextid(), $stored_file->get_component(), $stored_file->get_filearea(), $stored_file->get_itemid(), $stored_file->get_filepath(), $stored_file->get_filename());
                    // echo '<input type="image" src="'.$url.'"/>';
                    }
                    }
                    $mform->display();
                    }*/
                }
