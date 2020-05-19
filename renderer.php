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
 * @copyright  2020 PRN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

// Pour recuperer l'url de l'image.
require_once($CFG->dirroot.'/lib/weblib.php');
require_once($CFG->dirroot.'/lib/completionlib.php');


/**
 * tableau_bord block rendrer
 *
 * @copyright  2020 PRN 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
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
        $moodleurl = new moodle_url(null);
        $html = '<script src="'.$CFG->wwwroot.'/blocks/tableau_bord/Chart.js-master/Chart.js'.'"></script>
                 <script src="'.$CFG->wwwroot.'/blocks/tableau_bord/graphes.js'.'"></script>
                 <script src="'.$CFG->wwwroot.'/blocks/tableau_bord/xhr.js'.'"></script>';
        // Affiche les boutons du menu.
        $html .= $this->menu($courses);
        $config = get_config('block_tableau_bord');
        $courseordernumber = 0;
        $maxcourses = count($courses);

        $arraydate = array(); // Sera complété à chaque nouvelle année trouvée.
        // On parcours les cours pour récupérer toutes les "années" différentes.
        foreach ($courses as $key => $course) {
            $coursefullname = $course->fullname;
            $pattern = "/\[(20[0-9]{2}-20[0-9]{2})\]/";

            if (preg_match($pattern, $coursefullname, $matches)) {
                $arraydate[$matches[1]] = $matches[1]; // Si on trouve on l'ajoute au tab.
            }
        }
        // Ajouter une liste HTML avec les dates (pour la construction des onglets).
        if (!empty($arraydate)) {
            arsort($arraydate);
            $html .= '<div id="tabs">';
            $html .= '<ul class="tabs">';
            // Pour chaque "année" on ajoute une ligne, qui sera avec jQuery transformé en onglet.
            foreach ($arraydate as $date) {
                $html .= '<li><a href="#tabs-'.$date.'" class="tab'.$date.'">'.$date.'</a></li>';
            }
            $html .= "</ul>";

            // Code jQuery.
            $html .= '<script>window.onload = function() {';
            foreach ($arraydate as $date) { // Pour chaque "année, pour les rassembler dans un div".
                $html .= '$( ".'.$date.'" ).wrapAll( "<div id=\'tabs-'.$date.'\' />");';
            }
            $html .= '$( "#tabs" ).tabs();';

            // Ajout d'un texte pour prévenir de la nouveauté.
            $nvxtxt = 'Les cours sont  triés par année universitaire dans des onglets';
            $html .= '$(".welcome_title").append("<span class=\"nouveautetdb\">'.$nvxtxt.'</span>");';
            $html .= '};</script>';
        }

        foreach ($courses as $key => $course) {
            /* add jjupin "Onglets annualisation"
             * Pour chaque cours, ajouter une classe en fonction de l'année pour créer des onglets
             * On parse chaque nom de cours pour récuperer l'année => [20xx-20xx]
             */
            $coursefullname = $course->fullname;
            $courseclass = ""; // Sera complété si une date est trouvée.
            $pattern = "/\[(20[0-9]{2}-20[0-9]{2})\]/";

            if (preg_match($pattern, $coursefullname, $matches)) {
                $courseclass = $matches[1];
            } else {
                // Sinon on donne l'année en cours (la plus élevée = la 1e dans le tableau qui a été trié).
                $courseclass = reset($arraydate);
            }
            // End add jjupin.

            // Coursebox -> le bloc d'un cours.
            // Add jjupin 31/01/17 => ajouter une classe CSS si le cours est masqué (le griser).
            if (empty($course->visible)) {
                $courseclass .= ' course_not_visible ';
            }
            // End add jjupin 31/01/17.

            /* Defini les classes des cours en fonction de la valeur presente dans le cookie s'il existe.
             * Sinon defini par defaut la classe coursebox qui correspond a un affichage des cours en liste*/
            if (!empty($_COOKIE['disposition'])) {
                $html .= $this->output->box_start($_COOKIE['disposition']." ".$courseclass, "course-{$course->id}");
            } else {
                $html .= $this->output->box_start('coursebox '.$courseclass, "course-{$course->id}");
            }

            $html .= '<div class="cours-titre">';
            // Add jjupin 31/01/17 => ajouter une information si le cours est masqué.
            if (empty($course->visible)) {
                $html .= '(COURS MASQUÉ) ';
            }
            // End add jjupin 31/01/17.

            // No need to pass title through s() here as it will be done automatically by html_writer.
            $attributes = array('title' => $course->fullname);
            if ($course->id > 0) {
                if (empty($course->visible)) {
                    $attributes['class'] = 'dimmed';
                }
                $coursefullname = format_string($course->fullname, true, $course->id); //on peut mettre fullname/shortname.
                $html .= $coursefullname;
            } else {
                $html .= $this->output->heading(
                                        html_writer::link(new moodle_url('/auth/mnet/jump.php',
                                                                        array('hostid' => $course->hostid,
                                                                        'wantsurl' => '/course/view.php?id='.$course->remoteid)),
                                                          format_string($course->shortname, true),
                                                          $attributes)
                                        . ' (' . format_string($course->hostname) . ')', 2, 'title');
            }

            $html .= html_writer::end_tag('div'); // course_title (?)

            // RECUPERER  AVANCEMENT.
            // Recuperation du role de l'utilisateur dans le cours ainsi que  les informations concernant les activites du cours
            $context = context_course::instance($course->id, true);
            $roles = array();
            $roles = get_user_roles($context, $USER->id, true); // Retourne les roles de l'utilisateur pour le cours courant.
            $completion = new completion_info($course);
            // Recupere les activites faisant partie du suivi d'achevement
            $activities = $completion->get_activities();
            $nb_act = count($activities);

            $avancementGlobal = "";
            $avancementDetaille ="";
            $roleUtilisateur = "";
            // Si l'utilisateur connecté avec un role dans le cours et qu'il y a au moins une activite dans le suivi d'achevement
            if ($nb_act > 0) {
                foreach($roles as $role){ // Recuperation du role qui nous intéresse !
                    if (strcmp($role->shortname, "teacher" ) == 0 || strcmp($role->shortname, "editingteacher" ) == 0 ) {
                        $roleUtilisateur = "teacher";
                        break;
                    } elseif (strcmp($role->shortname, "student" ) == 0) {
                        $roleUtilisateur = "student";
                    }
                }
            } 

            // Recupere ce qui permet d'afficher le suivi d'avancement selon le role de l'utilisateur dans ce cours
            if ($roleUtilisateur == "teacher" ) {
                // Recupere les deux types d'avancement dans deux variables differentes
                list($avancementGlobal, $avancementDetaille) = $this->afficherAvtProf($course); 
            } elseif ($roleUtilisateur == "student") {
                // Recupere les deux types d'avancement dans deux variables differentes
                list($avancementGlobal, $avancementDetaille) = $this->afficherAvtEtu($course);
            }

            // Affiche l'avancement global s'il existe
            $html.= '<div class="cours-avancement-global">';
            if ($avancementGlobal !== "") {
                $html .= $avancementGlobal;
                $html .='<div class="clear"></div>';
            }
            $html .= html_writer::end_tag('div'); 

            // AFFICHER AVANCEMENT  DETAILLE & NOTIFICATIONS.
            $html.= '<div class="cours-infos">';

            // avancement ou si acucune activité. 
            if ($nb_act > 0) {
                $html .= $avancementDetaille;
            }

            // Notification : 
            if (isset($overviews[$course->id])) {
                $html .= $this->afficher_notification($course, $overviews);
            }
            $html .= html_writer::end_tag('div'); // fin de cours-infos

            if (!empty($config->showchildren) && ($course->id > 0)) {
                // List children here.
                if ($children = block_tableau_bord_get_child_shortnames($course->id)) {
                    $html .= html_writer::tag('span', $children, array('class' => 'coursechildren'));
                }
            }

            $courseordernumber++;
            // Bouton pour acceder au cours.
            $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
            $html .= '<div class="course-button"><a href="'.$courseurl.'">' .get_string('reach','block_tableau_bord').' </a></div>';
            $html .='<div class="clear"></div>'; 
            $html .= $this->output->box_end(); // Fin du coursebox
        }
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

    //   NOUVELLES FONCTIONS.

    public function afficherAvtProf($course){
        global $OUTPUT;
        $avancement_global = "";
        $avancement_detaille ="";
        ///////////////// Recuperation des donnees du cours (code de la page rapport d'activite) ////////////////////
        $context = context_course::instance($course->id);

        // Sort (default lastname, optionally firstname)
        $sort = optional_param('sort','',PARAM_ALPHA);
        $start   = optional_param('start', 0, PARAM_INT);

        // Get group mode.
        $group = groups_get_course_group($course,true); // Supposed to verify group
        if ($group === 0 && $course->groupmode == SEPARATEGROUPS) {
            require_capability('moodle/site:accessallgroups', $context);
        }

        $completion = new completion_info($course);
        $activities = $completion->get_activities(); // Activites faisant partie du suivi d'achevement

        // Generate where clause.
        $where = array();
        $where_params = array();

        // Get user match count.
        $total = $completion->get_num_tracked_users(implode(' AND ', $where), $where_params, $group);

        // Get user data
        $progress = array();

        if ($total) {
            $progress = $completion->get_progress_all(implode(' AND ', $where),
                            $where_params, $group, $sort, $total, $start, $context);
        }
        // Fin recuperation donnees.

        $nb_act_achevee_total=0; // Nombre activite achevees par tous les etudiants.
        $nb_act_non_achevee_total=0; // Nombre activite non achevees par tous les etudiants.

        $tableau_histo = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0); // Tableau pour construire l'histogramme.

        // Pour chaque utilisateur.
        foreach ($progress as $user) {
            $nb_act_achevee = 0; // Nombre d'activite achevees par l'utilisateur
            $nb_act_non_achevee = 0; // Nombre d'activite non achevees par l'utilisateur
            $pourcentage_achevement = 0;

            // Pour chaque activite faisant partie du suivi d'achevement
            foreach ($activities as $activity) {
                // Recuperation de l'etat de l'activite.
                if (array_key_exists($activity->id,$user->progress)) {
                    $thisprogress = $user->progress[$activity->id];
                    $state = $thisprogress->completionstate;
                } else {
                    $state = COMPLETION_INCOMPLETE;
                }

                // Incremente les variables en fonction de l'etat de l'activite.
                switch($state) {
                    case COMPLETION_INCOMPLETE : $nb_act_non_achevee++;$nb_act_non_achevee_total++; break;
                    case COMPLETION_COMPLETE : $nb_act_achevee++;$nb_act_achevee_total++; break;
                    case COMPLETION_COMPLETE_PASS : $nb_act_achevee++;$nb_act_achevee_total++;break;
                    case COMPLETION_COMPLETE_FAIL : $nb_act_non_achevee++;$nb_act_non_achevee_total++; break;
                }
            }

            $pourcentage_achevement = $nb_act_achevee/count($activities) * 100; // Pourcentage d'activite completees.
            $palier = floor($pourcentage_achevement/10); // Recupere l'indice correspondant a un palier d'avancement.
            $tableau_histo[$palier]++; // Incremente le nombre d'etudiant dans le palier correspondant.
        }

        // Pourcentage de l'avancement moyen de tous les utilisateurs.
        $pourcentage_acheve = ($nb_act_achevee_total / ($nb_act_non_achevee_total + $nb_act_achevee_total)) * 100;
        $pourcentage_non_acheve = ($nb_act_non_achevee_total / ($nb_act_non_achevee_total + $nb_act_achevee_total)) * 100;

        $id_canvas_histo = "canvasHisto".$course->id;
        $id_canvas_global = "affichage-global-cours-".$course->id;
 
        $histogramme =""; // Contient le code html permettant d'afficher l'histogramme.
        // Creation du canvas pour l'histo et des variable JS a passer en parametre de la fonction JS qui creera l'histo
        $histogramme .= '<canvas id="'.$id_canvas_histo.'"  width="450"></canvas>'.
                        '<script type="text/javascript">
                          var canvasHisto = "'.$id_canvas_histo.'";
                          var tableauHisto = new Array();';   
        for ($i = 0; $i < count($tableau_histo); $i++) { // Rempli le tableau javascript a l'aide du tableau php
            $histogramme .= "tableauHisto[".$i."] = " . $tableau_histo[$i] . ";";
        }

        // Lien vers le rapport "achèvement d'activités" dans le cours.
        $url = new moodle_url("/report/tuteur/index.php", array('course' => $course->id));

        // Creation de l'histogramme pour le cours
        $histogramme .= 'creerBar(canvasHisto,tableauHisto); </script>
                        <center><b>Nombre d\'étudiants par pourcentage d\'avancement</b>'
                        . $OUTPUT->help_icon('teacherdetailedprogress','block_tableau_bord')
                        . '</center>'
                        .'<div class = "link-button"> <a href="'.$url.'">Afficher l\'achèvement d\'activités par étudiant </a> </div>';
        // Creation de l'affichage global pour le cours (camembert)
        $avancement_global .= '<center><canvas id="'.$id_canvas_global.'" height="90" width="120"></canvas></center>
                    <script>
                    var canvasGlobal="affichage-global-cours-'.$course->id.'";
                    var pourcentage_act_complet_'.$course->id.' ='.$pourcentage_acheve.';
                    var pourcentage_act_incomplet_'.$course->id.' ='.$pourcentage_non_acheve.';
                    creerPieProf(canvasGlobal, pourcentage_act_complet_'.$course->id.', pourcentage_act_incomplet_'.$course->id.');
                    </script>
                    <center><b>'. get_string('teacherprogress','block_tableau_bord')
                    . ' : '.intval($pourcentage_acheve).' % </b> '
                    . $OUTPUT->help_icon('teacherglobalprogress','block_tableau_bord').'</center>';

        // Contient l'histogramme dans une menu deroulant
        $avancement_detaille .= $this->collapsible_region($histogramme, '', 'region_histo_'.$course->id, 
                    '<img src="'. $OUTPUT->image_url('avancement_18', 'block_tableau_bord').'" alt="Avancement icon" /> '.
                    '<b>'.get_string('seeprogressteacher','block_tableau_bord').'</b>', '', true);
        // Retourne les deux types d'avancement separement
        return array($avancement_global,$avancement_detaille);
    }

    public function afficherAvtEtu($course) {
        global $OUTPUT;
        $avancement_global =""; // Contient le code html affichant l'avancement global.
        $avancement_detaille =""; // Contient le code html affichant l'avancement detaille.

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

        // Compte le nombre total d'activites achevees et non achevees pour differents types d'activite
        // (la table "mdl_modules" liste les types)
        foreach ($activities as $activity) {
            $data = $completion->get_data($activity);
            // Si l'activite est achevee
            if ($data->completionstate) {
                $nb_act_achevee = $nb_act_achevee + 1;
                if ($activity->modname == 'assign') {
                    $devoir_acheve = $devoir_acheve + 1;
                } elseif ($activity->modname == 'lesson') {
                    $lecon_achevee = $lecon_achevee + 1;
                } elseif ($activity->modname == 'resource') {
                    $ressource_achevee = $ressource_achevee + 1;
                } elseif ($activity->modname == 'quiz') {
                    $test_acheve = $test_acheve + 1;
                } else {
                    $autre_acheve = $autre_acheve + 1;
                }
            } else {
                $nb_act_non_achevee++;
                if ($activity->modname == 'assign') {
                    $devoir_non_acheve = $devoir_non_acheve + 1;
                } elseif ($activity->modname == 'lesson') {
                    $lecon_non_achevee = $lecon_non_achevee + 1;
                } elseif ($activity->modname == 'resource') {
                    $ressource_non_achevee = $ressource_non_achevee + 1;
                } elseif ($activity->modname == 'quiz') {
                    $test_non_acheve = $test_non_acheve + 1;
                } else {
                    $autre_non_acheve = $autre_non_acheve + 1;
                }
            }
        }

        $pourcentage_achevee = $nb_act_achevee/$nb_act*100; // Pourcentage d'activites achevees
        $pourcentage_non_achevee = 100 - $pourcentage_achevee; // Pourcentage d'activites non achevees

        $avancement_activite =""; // Contient l'affichage detaille (un graphe pour un type d'activite)

        // Pourcentage de completion pour chaque type d'activite ainsi qu'ajout du canvas et du graphe dans celui-ci
        if ($devoir_acheve + $devoir_non_acheve > 0) {
            $pourcentage_devoir_acheve = $devoir_acheve / ($devoir_acheve + $devoir_non_acheve) * 100;
            $pourcentage_devoir_non_acheve = 100 - $pourcentage_devoir_acheve;
            $avancement_activite .=  '<div class = canvasDetaille><center><b>'. get_string('assign','block_tableau_bord') .'</b></center>
                            <canvas id="affichage-devoir-cours-'.$course->id.'" height="60" width="80"></canvas>';

            // Affiche le nombre de devoirs restants
            if ($devoir_non_acheve <= 1) {
                $avancement_activite .= '<center>'. get_string('oneactivityremaining','block_tableau_bord',$devoir_non_acheve) .'</center></div>';
            } else {
                $avancement_activite .= '<center>'. get_string('someactivityremaining','block_tableau_bord',$devoir_non_acheve) .'</center></div>';
            }

            $avancement_activite .= '<script>
                        var canvas_devoir = "affichage-devoir-cours-'.$course->id.'";
                        var pourcentage_devoir_acheve_'.$course->id.' ='.$pourcentage_devoir_acheve.';
                        var pourcentage_devoir_non_acheve_'.$course->id.' ='.$pourcentage_devoir_non_acheve.';

                        creerPieDetaille(canvas_devoir, pourcentage_devoir_acheve_'.$course->id.', pourcentage_devoir_non_acheve_'.$course->id.');
                        </script>';
        }

        if ($lecon_achevee + $lecon_non_achevee > 0) {
            $pourcentage_lecon_achevee = $lecon_achevee / ($lecon_achevee + $lecon_non_achevee) * 100;
            $pourcentage_lecon_non_achevee = 100 - $pourcentage_lecon_achevee;
            $avancement_activite .=	'<div class = canvasDetaille><center><b>'. get_string('lesson','block_tableau_bord') .'</b></center>
                            <canvas id="affichage-lecon-cours-'.$course->id.'" height="60" width="80"></canvas>';

            // Affiche le nombre de lecons restantes
            if ($lecon_non_achevee <= 1) {
                $avancement_activite .= '<center>'. get_string('oneactivityfeminineremaining','block_tableau_bord',$lecon_non_achevee) .'</center></div>';
            } else {
                $avancement_activite .= '<center>'. get_string('someactivityfeminineremaining','block_tableau_bord',$lecon_non_achevee) .'</center></div>';
            }

            $avancement_activite .= '<script>
                        var canvas_lecon = "affichage-lecon-cours-'.$course->id.'";
                        var pourcentage_lecon_achevee_'.$course->id.' ='.$pourcentage_lecon_achevee.';
                        var pourcentage_lecon_non_achevee_'.$course->id.' ='.$pourcentage_lecon_non_achevee.';

                        creerPieDetaille(canvas_lecon, pourcentage_lecon_achevee_'.$course->id.', pourcentage_lecon_non_achevee_'.$course->id.');
                        </script>';
        }

        if ($ressource_achevee + $ressource_non_achevee > 0) {
            $pourcentage_ressource_achevee = $ressource_achevee / ($ressource_achevee + $ressource_non_achevee) * 100;
            $pourcentage_ressource_non_achevee = 100 - $pourcentage_ressource_achevee;
            $avancement_activite .= '<div class = canvasDetaille><center><b>'. get_string('resource','block_tableau_bord') .'</b></center>
                            <canvas id="affichage-ressource-cours-'.$course->id.'" height="60" width="80"></canvas>';

            // Affiche le nombre de ressources restantes
            if ($ressource_non_achevee <= 1) {
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

        if ($test_acheve + $test_non_acheve > 0) {
            $pourcentage_test_acheve = $test_acheve / ($test_acheve + $test_non_acheve) * 100;
            $pourcentage_test_non_acheve = 100 - $pourcentage_test_acheve;
            $avancement_activite .= '<div class = canvasDetaille><center><b>'. get_string('quiz','block_tableau_bord') .'</b></center>
                            <canvas id="affichage-test-cours-'.$course->id.'" height="60" width="80"></canvas>';
            // Affiche le nombre de ressources restantes
            if ($test_non_acheve <= 1) {
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

        if ($autre_acheve + $autre_non_acheve > 0) {
            $pourcentage_autre_acheve = $autre_acheve / ($autre_acheve + $autre_non_acheve) * 100;
            $pourcentage_autre_non_acheve = 100 - $pourcentage_autre_acheve;
            $avancement_activite .= '<div class = canvasDetaille><center><b>'. get_string('otheractivity','block_tableau_bord') .'</b></center>
                            <canvas id="affichage-autre-cours-'.$course->id.'" height="60" width="80"></canvas>';

            // Affiche le nombre d'autres activites restantes
            if ($autre_non_acheve <= 1) {
                $avancement_activite .= '<center>'. get_string('oneactivityfeminineremaining','block_tableau_bord',$autre_non_acheve) .'</center></div>';
            } else {
                $avancement_activite .= '<center>'. get_string('someactivityfeminineremaining','block_tableau_bord',$autre_non_acheve) .'</center></div>';
            }

            $avancement_activite .= '<script>
                        var canvas_autre = "affichage-autre-cours-'.$course->id.'";
                        var pourcentage_autre_acheve_'.$course->id.' ='.$pourcentage_autre_acheve.';
                        var pourcentage_autre_non_acheve_'.$course->id.' ='.$pourcentage_autre_non_acheve.';

                        creerPieDetaille(canvas_autre, pourcentage_autre_acheve_'.$course->id.', pourcentage_autre_non_acheve_'.$course->id.');
                        </script>';
        }
        // info bulle à la fin : 
        $avancement_activite .= $OUTPUT->help_icon('studentdetailedprogress','block_tableau_bord');

        // Cree le diagramme d'avancement global
        $avancement_global .= ' <center><canvas id="affichage-global-cours-'.$course->id.'" height="90" width="120" ></canvas></center>
                    <script>
                    var canvas = "affichage-global-cours-'.$course->id.'";
                    var pourcentage_act_achevee_'.$course->id.' ='.$pourcentage_achevee.';
                    var pourcentage_act_non_achevee_'.$course->id.' ='.$pourcentage_non_achevee.';
                    creerPie(canvas, pourcentage_act_achevee_'.$course->id.', pourcentage_act_non_achevee_'.$course->id.');
                    </script>

                    <center><b>'. get_string('studentprogress','block_tableau_bord').' : '.intval($pourcentage_achevee).' %</b>
                    '.$OUTPUT->help_icon('studentglobalprogress','block_tableau_bord').'</center>';

        // Contient les diagrammes d'avancement des types d'activite dans un menu deroulant
        $avancement_detaille .= $this->collapsible_region(
                                        $avancement_activite, '', 'region_detaille_'.$course->id,
                                        '<img src="'. $OUTPUT->image_url('avancement_18', 'block_tableau_bord').'"
                                              alt="Avancement icon" /> <b>'.get_string('seeprogress','block_tableau_bord').'</b>',
                                        '', true);

        // Renvoie les deux types d'avancement separement
        return array($avancement_global,$avancement_detaille);
    }

    public function menu($courses){
        global $USER,$CFG;
        // Div pour le menu.
        $html = '<div class="menu">';
        $html .= '<div class="welcome_title"><p>';
        $html .= get_string('plugintitle', 'block_tableau_bord');
        $html .= '</p></div>';

        // Userediting : booleen vrai si l'utilisateur est en cours de modification de l'ordre des cours, faux sinon.
        $userediting = !empty($USER->userediting_course) && $USER->userediting_course == true;
        $html .= '<div class = "formulaire">';
        // Si en mode edition, on affiche le bouton pour activer le drag and drop.
        // Sinon bouton pour valider le choix une fois que l'utilisateur a reorganise ses cours.
        if ($userediting) {
            $html .= '<form method="post" action="'. $CFG->wwwroot.'/blocks/tableau_bord/ordre_cours.php'.'">
                      <p> <input type="submit" value="'. get_string('validorder', 'block_tableau_bord') .'"
                                 title="validerCours" name="valider" /> </p>
                      </form>';
        } else {
            $html .= '<form id = "ordre_cours" method="post" action="'.$CFG->wwwroot.'/blocks/tableau_bord/ordre_cours.php'.'">
                      <p> <input class="modifier_ordre_cours" type="submit"
                          value="'. get_string('changeorder', 'block_tableau_bord') .'" name="ordre_cours" /> </p>
                      </form>';
        }
        $html .= '</div>';

        // Fin de la div du menu du haut.
        $html .= $this->output->box('', 'flush');
        $html .= '</div>';
        return $html;
    }

    /**
     * Fonction qui cree et retourne le menu deroulant avec toutes les notifications pour un seul cours
     *     ainsi que leur bouton de suppression
     * Retourne une variable au format html
     */
    public function afficher_notification($course, $overviews) {
        global $USER, $DB, $OUTPUT, $CFG;

        $notifactivite = "";          // Contient toutes les notifications d'un type d'activite.
        $notifactivitecomplete = ""; // Contient toutes les notifications d'un type d'activite plus le titre.
        $html = '';                 // Contient toutes les notifications dans un menu deroulant.
        $nbtypeactivite = 0;       // Nombre de types d'activite different a afficher : correspond au nombre de notifications.

        // Pour chaque type d'activite du cours.
        foreach (array_keys($overviews[$course->id]) as $module) {
            $activiteaffichee = 0; // Nb activite qui sont affichees : au depart 0.
            $presencenotif = false;   // Booleen qui indique la presence de notification(s) affichee(s) ou non.

            // Pour chaque notification de ce type d'activite.
            foreach ($overviews[$course->id][$module] as $coursemodule=>$notif) {
                $param_notif = array('id_user' => $USER->id, 'id_course_module' => $coursemodule);
                $presencenotif = true;

                // Lors du clic sur le bouton de suppression, un formulaire est appelé en JS/ajax.
                // Si cette notification est la derniere de son type d'activite dans un cours.
                // On supprime le titre du type de notification.
                $notifactivite .= '
                            <script>
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
                                // Appelle un formulaire pour ajouter a la table la notification qd clic sur le bouton suppression.
                                xhr.open("POST", "'.$CFG->wwwroot.'/blocks/tableau_bord/suppression_notif.php'.'", true);
                                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                                xhr.send("id_activite='.$coursemodule.'&id_user='.($USER->id).'");
                            }
                            </script>';

                // Creation de la division qui contient la notification en elle meme ainsi que le bouton de suppression.
                $notifactivite .= '<div id='.$coursemodule.' class="notif">';
                $notifactivite .= $notif;
                // Bouton de suppression de la notification.
                $notifactivite .= '<a id="bouton'.$coursemodule.'" class="boutonSupprimer"
                                title="Cliquer ici pour supprimer la notification"
                                href=\'javascript:document.effacerNotif'.$coursemodule.'();\'>
                                <div class="delete_notif">X</div></a>';

                $notifactivite .= '</div>';
                $notifactivite .= '<div class="clear"></div>';

                $activiteaffichee ++; // Le nombre d'activite qui sont effectivement affichees
            }
            // Cree une variable JS contenant le nombre de notifs affichees pour un type d'activite donne dans un cours
            // Quand elle sera a zero elle permettra d'effacer le titre de ces notifs (ex: "vous avez des devoirs...")
            $notifactivite .= '<script> var compteur_notif_' . $module . '_course_' .
                                ($course->id).' = '.$activiteaffichee.';</script>';

            $titrenotiftypeactivite = ""; // Contient le code html qui permet d'afficher le titre du type des notifs.

            // S'il y a une notif de ce type on incremente le nombre de types differents de notifs.
            // Et on construit le titre de ce(s) notif(s).
            if ($presencenotif) {
                $nbtypeactivite++;
                // Recuperation du titre de la notif pour un type d'activite ainsi que l'image correspondant.
                $url = new moodle_url("/mod/$module/index.php", array('id' => $course->id));

                $typeactivite = get_string('modulename'.$module, 'block_tableau_bord');

                // Construction du titre pour un type de notification.
                $titrenotiftypeactivite .= '<div id="notif-'.$module.'-course-'.($course->id).'">';
                $titrenotiftypeactivite .= html_writer::link($url, $this->output->pix_icon('icon', $typeactivite,
                                                                        'mod_'.$module, array('class' => 'iconlarge')));
                $titrenotiftypeactivite .= '<b>';
                $titrenotiftypeactivite .= get_string("activityoverview".$module, "block_tableau_bord");
                $titrenotiftypeactivite .= '</b></div>';
            }

            // On concatene les notifs d'un meme type d'activite avec le titre correspondant.
            $notifactivitecomplete .= $titrenotiftypeactivite . $notifactivite;
            $notifactivite = "";
        }

        // Initialisation de la variable contenant le nombre de type d'activite effectivement affiche.
        // Permet de masquer le menu deroulant lorsque toutes les notifs de chaque type d'activites ont ete supprimees.
        $html .= '<script> var nombre_type_activite_affiches_'.($course->id).' = '.$nbtypeactivite.';</script>';

        // Construit la phrase indiquant la presence de notification.
        if ($nbtypeactivite > 0) {
            if ($nbtypeactivite == 1) {
                $icontext = '<img src="'. $OUTPUT->image_url('notification_18', 'block_tableau_bord')
                            . '" alt="Notification icon" /> <b>'
                            . get_string('youhaveanotification', 'block_tableau_bord').'</b>';
            } else {
                $icontext = '<img src="'. $OUTPUT->image_url('notification_18', 'block_tableau_bord')
                            . '" alt="Notification icon" /> <b>'
                            . get_string('youhavesomenotifications', 'block_tableau_bord', $nbtypeactivite).'</b>';
            }

            // Met la variable contenant toutes les notifications ainsi qu'un bouton d'aide dans un menu deroulant.
            $html .= $this->collapsible_region(
                                $notifactivitecomplete.$OUTPUT->help_icon('notifications', 'block_tableau_bord'),
                                '',
                                'region_notification_'.$course->id,
                                $icontext,
                                '',
                                true);
        }
        return $html;
    }
}
