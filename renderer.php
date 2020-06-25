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
     * @param array $arronglet list of tab
     * @return string html to be displayed in tableau_bord block
     */
    public function tableau_bord($courses, $overviews, $arraydate) {
        global $CFG, $USER;
        $html = '<script src="'.$CFG->wwwroot.'/blocks/tableau_bord/js/Chart.min.js'.'"></script>
                 <script src="'.$CFG->wwwroot.'/blocks/tableau_bord/js/tablist.min.js'.'"></script>
                 <script src="'.$CFG->wwwroot.'/blocks/tableau_bord/graphes.js'.'"></script>
                 <script src="'.$CFG->wwwroot.'/blocks/tableau_bord/xhr.js'.'"></script>';

        $courseordernumber = 0;

        // Le contenu du plugin TDB.
        $html .= '<div class="tdb-container container-fluid">';

        // Ajouter une liste HTML avec les dates (pour la construction des onglets).
        if (!empty($arraydate)) {
            $annee = intval(date("Y"));
            $mois = intval(date("n"));
            if ($mois < 8) {
                $encours = "" . ($annee - 1) . "-" .$annee;
            } else {
                $encours = "" . $annee . "-" .($annee + 1);
            }
            $html .= '<div id="LMtabs" class="row w-100">';
            $html .= '<ul role="ongletlist" class="mb-0">';
            $cpt = 1;
            foreach ($arraydate as $date) {
                $idpan = 'panneau-'.$cpt++;
                if (strcmp($encours, $date) == 0) {
                    $html .= '<li role="tab" aria-controls="'. $idpan .'" data-open="true">' . $date . '</li>';
                } else {
                    $html .= '<li role="tab" aria-controls="'. $idpan .'" >' . $date . '</li>';
                }
            }
            $html .= '</ul>';
            $html .= '</div>';
        }

        // Div qui contient la liste des cours TDB.
        $html .= '<div class="cours-liste col-12">';
        $html .= '<div class="row-fluid no-gutters">';

        $cpt = 1;
        $reperedate = "";
        $pattern = "/\[(20[0-9]{2}-20[0-9]{2})\]/";

        foreach ($courses as $key => $course) {
            $coursefullname = $course->fullname;
            $dated = "";
            if (preg_match($pattern, $coursefullname, $matches)) {
                $dated = $matches[1];
            }
            if ($dated != "" && strcmp($reperedate, $dated) != 0) {
                if (!empty($reperedate)) {
                    $html .= '</div>';
                }
                $reperedate = $dated;
                $idpan = 'panneau-'.$cpt++;
                $html .= '<div role="tabpanel" id="'. $idpan .'">';
            }

            // Div qui contient chaque element d'un cours dans le TDB.
            $html .= '<div class="cours-contenu row-fluid w-100 border">';

            // Titre du cours.
            $html .= '<h3 class="cours-titre col-md-12 p-3">';
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
                $coursefullname = format_string($course->fullname, true, $course->id);
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
             $html .= "</h3>";  // Fin: Titre du cours.

            // RECUPERER  AVANCEMENT.
            // Recuperation du role de l'utilisateur dans le cours ainsi que  les informations concernant les activites du cours.
            $context = context_course::instance($course->id, true);
            $roles = array();
            $roles = get_user_roles($context, $USER->id, true); // Retourne les roles de l'utilisateur pour le cours courant.
            $completion = new completion_info($course);
            // Recupere les activites faisant partie du suivi d'achevement.
            $activities = $completion->get_activities();
            $nbact = count($activities);

            $avancementglobal = "";
            $avancementdetaille = "";
            $roleutilisateur = "";
            // Si l'utilisateur connecté avec un role dans le cours et qu'il y a au moins une activite dans le suivi d'achevement.
            if ($nbact > 0) {
                foreach ($roles as $role) { // Recuperation du role qui nous intéresse !
                    if (strcmp($role->shortname, "teacher" ) == 0 || strcmp($role->shortname, "editingteacher" ) == 0 ) {
                        $roleutilisateur = "teacher";
                        break;
                    } else if (strcmp($role->shortname, "student" ) == 0) {
                        $roleutilisateur = "student";
                    }
                }
            }

            // Recupere ce qui permet d'afficher le suivi d'avancement selon le role de l'utilisateur dans ce cours.
            if ($roleutilisateur == "teacher" ) {
                // Recupere les deux types d'avancement dans deux variables differentes.
                list($avancementglobal, $avancementdetaille) = $this->afficher_avt_prof($course);
            } else if ($roleutilisateur == "student") {
                // Recupere les deux types d'avancement dans deux variables differentes.
                if ($course->enablecompletion == 1) {
                    list($avancementglobal, $avancementdetaille) = $this->afficher_avt_etu($course);
                }
            }

            // Affiche l'avancement global s'il existe.
            $html .= '<div class="cours-avancement-global  col-md-3" >';
            if ($avancementglobal !== "") {
                $html .= $avancementglobal;
            }
            $html .= "</div>"; // Fin: avancement global.

            // AFFICHER AVANCEMENT  DETAILLE & NOTIFICATIONS.
            $html .= '<div class="cours-infos col-md-5">';

            // Avancement ou si acucune activité.
            if ($nbact > 0) {
                $html .= $avancementdetaille;
            }

            // Notification.
            if (isset($overviews[$course->id])) {
                $html .= $this->afficher_notification($course, $overviews);
            }

            $html .= "</div>"; // Fin: cours-infos.
            $courseordernumber++;
            // Bouton pour acceder au cours.
            $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
            $html .= '<div class="col-md-4 mb-2 mt-2"><a class="btn btn-primary btn-lg w-100" href="'.$courseurl.'">' .
                    get_string('reach', 'block_tableau_bord').' </a></div>';

            $html .= "</div>"; // Fin du cours-contenu.
        }

         $html .= "</div></div>"; // Fin du cours-liste (2 div).

        if ($cpt > 1) {
            // Code js.
            $html .= '<script>var list = document.querySelector( \'[role="ongletlist"]\' );';
            $html .= 'var tablist = new window.Tablist( list );';
            $html .= 'tablist.on( \'show\', function( tab, tabPanel ){';
            $html .= 'tab.style.backgroundColor = "#C0C0C0";';
            $html .= 'tab.style.color = "#000000";';
            $html .= '});';
            $html .= 'tablist.on( \'hide\', function( tab, tabPanel ){';
            $html .= 'tab.style.backgroundColor = "#FFFFFF";';
            $html .= 'tab.style.color = "#8080FF";';
            $html .= '});';
            $html .= 'tablist.mount();';

            $html .= "$(document).ready(function() {
          $('.LMnav-toggle').click(function(){
            var collapse__content__selector = $(this).attr('href');
            var toggle__switch = $(this);
            $(collapse__content__selector).toggle();
          });
        });";

            $html .= '</script>';
        }

        $html .= '</div>'; // Fin: tdb-container.

        return $html;
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
     * @param string $id id added to the div that is output. Must not be blank.
     * @param string $caption text displayed at the top. Clicking on this will cause the region to expand or contract.
     * @return bool if true, return the HTML as a string, rather than printing it.
     */
    protected function collapsible_region($contents, $id, $caption) {
        $output = '<div><a href="#'.$id.'" class="LMnav-toggle">'.$caption.'</a></div>';
        $output .= '<div id="'.$id.'" style="display:none">';
        $output .= $contents;
        $output .= '</div>';
        return $output;
    }

    /**
     * Creates html for welcome area
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

        $output .= '<div class="welcome_title" style="font-size: 27px;color : #0088CC;">';
        $output .= ">>" . get_string('plugintitle', 'block_tableau_bord');
        $output .= '</div>';
        return $output;
    }

    public function afficher_avt_prof($course) {
        $avancementglobal = "";
        $avancementdetaille = "";
        // Recuperation des donnees du cours (code de la page rapport d'activite).
        $context = context_course::instance($course->id);

        // Sort (default lastname, optionally firstname).
        $sort = optional_param('sort', '', PARAM_ALPHA);
        $start = optional_param('start', 0, PARAM_INT);

        // Get group mode.
        $group = groups_get_course_group($course, true); // Supposed to verify group.
        if ($group === 0 && $course->groupmode == SEPARATEGROUPS) {
            require_capability('moodle/site:accessallgroups', $context);
        }

        $completion = new completion_info($course);
        $activities = $completion->get_activities(); // Activites faisant partie du suivi d'achevement.

        // Generate where clause.
        $where = array();
        $whereparams = array();

        // Get user match count.
        $total = $completion->get_num_tracked_users(implode(' AND ', $where), $whereparams, $group);

        // Get user data.
        $progress = array();

        if ($total) {
            $progress = $completion->get_progress_all(implode(' AND ', $where),
                            $whereparams, $group, $sort, $total, $start, $context);
        }
        // Fin recuperation donnees.

        $nbactacheveetotal = 0; // Nombre activite achevees par tous les etudiants.
        $nbactnonacheveetotal = 0; // Nombre activite non achevees par tous les etudiants.

        $tableauhisto = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0); // Tableau pour construire l'histogramme.

        // Pour chaque utilisateur.
        foreach ($progress as $user) {
            $nbactachevee = 0; // Nombre d'activite achevees par l'utilisateur.
            $nbactnonachevee = 0; // Nombre d'activite non achevees par l'utilisateur.
            $pcentachevement = 0;

            // Pour chaque activite faisant partie du suivi d'achevement.
            foreach ($activities as $activity) {
                // Recuperation de l'etat de l'activite.
                if (array_key_exists($activity->id, $user->progress)) {
                    $thisprogress = $user->progress[$activity->id];
                    $state = $thisprogress->completionstate;
                } else {
                    $state = COMPLETION_INCOMPLETE;
                }

                // Incremente les variables en fonction de l'etat de l'activite.
                switch($state) {
                    case COMPLETION_INCOMPLETE :
                    case COMPLETION_COMPLETE_FAIL :
                        $nbactnonachevee++;
                        $nbactnonacheveetotal++;
                        break;
                    case COMPLETION_COMPLETE :
                    case COMPLETION_COMPLETE_PASS :
                        $nbactachevee++;
                        $nbactacheveetotal++;
                        break;
                }
            }

            $pcentachevement = $nbactachevee / count($activities) * 100; // Pourcentage d'activite completees.
            $palier = floor($pcentachevement / 10); // Recupere l'indice correspondant a un palier d'avancement.
            $tableauhisto[$palier]++; // Incremente le nombre d'etudiant dans le palier correspondant.
        }

        // Pourcentage de l'avancement moyen de tous les utilisateurs.
        $pcentacheve = ($nbactacheveetotal / ($nbactnonacheveetotal + $nbactacheveetotal)) * 100;
        $pcentnonacheve = ($nbactnonacheveetotal / ($nbactnonacheveetotal + $nbactacheveetotal)) * 100;

        $idcanvashisto = "canvasHisto" . $course->id;
        $idcanvasglobal = "affichage-global-cours-" . $course->id;

        $histogramme = ""; // Contient le code html permettant d'afficher l'histogramme.
        // Creation du canvas pour l'histo et des variable JS a passer en parametre de la fonction JS qui creera l'histo.
        $histogramme .= '<canvas id="'.$idcanvashisto.'"  width="450"></canvas>'.
                        '<script type="text/javascript">
                          var canvasHisto = "'.$idcanvashisto.'";
                          var tableauHisto = new Array();';
        for ($i = 0; $i < count($tableauhisto); $i++) { // Rempli le tableau javascript a l'aide du tableau php.
            $histogramme .= "tableauHisto[".$i."] = " . $tableauhisto[$i] . ";";
        }

        // Lien vers le rapport "achèvement d'activités" dans le cours.
        $url = new moodle_url("/report/tuteur/index.php", array('course' => $course->id));

        // Creation de l'histogramme pour le cours.
        $histogramme .= 'creerBar(canvasHisto,tableauHisto); </script>
                        <center><b>Nombre d\'étudiants par pourcentage d\'avancement</b>'
                        . $this->output->help_icon('teacherdetailedprogress', 'block_tableau_bord')
                        . '</center>'
                        .'<div class = "link-button"> <a href="' .
                        $url . '">Afficher l\'achèvement d\'activités par étudiant </a> </div>';
        // Creation de l'affichage global pour le cours (camembert).
        $avancementglobal .= '<center><canvas id="'.$idcanvasglobal.'" height="90" width="120"></canvas></center>
                    <script>
                    var canvasGlobal="affichage-global-cours-'.$course->id.'";
                    var pourcentage_act_complet_'.$course->id.' ='.$pcentacheve.';
                    var pourcentage_act_incomplet_'.$course->id.' ='.$pcentnonacheve.';
                    creerPieProf(canvasGlobal, pourcentage_act_complet_'.$course->id.', pourcentage_act_incomplet_'.$course->id.');
                    </script>
                    <center><b>'. get_string('teacherprogress', 'block_tableau_bord')
                    . ' : '.intval($pcentacheve).' % </b> '
                    . $this->output->help_icon('teacherglobalprogress', 'block_tableau_bord').'</center>';

        // Contient l'histogramme dans une menu deroulant.
        $avancementdetaille .= $this->collapsible_region($histogramme, 'region_histo_'.$course->id,
                    '<img src="'. $this->output->image_url('avancement_18', 'block_tableau_bord').'" alt="Avancement icon" /> '.
                    '<b>'.get_string('seeprogressteacher', 'block_tableau_bord').'</b>');

        // Retourne les deux types d'avancement separement.
        return array($avancementglobal, $avancementdetaille);
    }

    public function afficher_avt_etu($course) {
        $avancementglobal = ""; // Contient le code html affichant l'avancement global.
        $avancementdetaille = ""; // Contient le code html affichant l'avancement detaille.

        $completion = new completion_info($course);

        // TOUTES les infos de toutes les activites faisant partie du suivi d'achevement.
        $activities = $completion->get_activities();
        $nbact = count($activities); // Nombre total d'activites.

        $context = context_course::instance($course->id);

        $nbactachevee = 0; // Nombre total d'activites achevees.
        $nbactnonachevee = 0; // Nombre total d'activites non achevees.

        // Nombre d'activites achevees et non achevees pour differents types d'activites.
        $devoiracheve = 0;
        $devoirnonacheve = 0;
        $leconachevee = 0;
        $leconnonachevee = 0;
        $ressourceachevee = 0;
        $ressourcenonachevee = 0;
        $testacheve = 0;
        $testnonacheve = 0;
        $autreacheve = 0;
        $autrenonacheve = 0;

        // Compte le nombre total d'activites achevees et non achevees pour differents types d'activite
        // (la table "mdl_modules" liste les types).
        foreach ($activities as $activity) {
            $data = $completion->get_data($activity);
            // Si l'activite est achevee.
            if ($data->completionstate) {
                $nbactachevee = $nbactachevee + 1;
                if ($activity->modname == 'assign') {
                    $devoiracheve++;
                } else if ($activity->modname == 'lesson') {
                    $leconachevee++;
                } else if ($activity->modname == 'resource') {
                    $ressourceachevee++;
                } else if ($activity->modname == 'quiz') {
                    $testacheve++;
                } else {
                    $autreacheve++;
                }
            } else {
                $nbactnonachevee++;
                if ($activity->modname == 'assign') {
                    $devoirnonacheve++;
                } else if ($activity->modname == 'lesson') {
                    $leconnonachevee++;
                } else if ($activity->modname == 'resource') {
                    $ressourcenonachevee++;
                } else if ($activity->modname == 'quiz') {
                    $testnonacheve++;
                } else {
                    $autrenonacheve++;
                }
            }
        }

        $pcentacheve = $nbactachevee / $nbact * 100; // Pourcentage d'activites achevees.
        $pcentnonacheve = 100 - $pcentacheve; // Pourcentage d'activites non achevees.

        $avtactivite = ""; // Contient l'affichage detaille (un graphe pour un type d'activite).

        // Pourcentage de completion pour chaque type d'activite ainsi qu'ajout du canvas et du graphe dans celui-ci.
        if ($devoiracheve + $devoirnonacheve > 0) {
            $pcentdevoiracheve = $devoiracheve / ($devoiracheve + $devoirnonacheve) * 100;
            $pcentdevoirnonacheve = 100 - $pcentdevoiracheve;
            $avtactivite .= '<div class = canvasDetaille>';
            $avtactivite .= '<center><b>'. get_string('assign', 'block_tableau_bord') .'</b></center>';
            $avtactivite .= '<canvas id="affichage-devoir-cours-'. $course->id. '" height="60" width="80"></canvas>';

            // Affiche le nombre de devoirs restants.
            $avtactivite .= '<center>';
            if ($devoirnonacheve <= 1) {
                $avtactivite .= get_string('oneactivityremaining', 'block_tableau_bord', $devoirnonacheve);
            } else {
                $avtactivite .= get_string('someactivityremaining', 'block_tableau_bord', $devoirnonacheve);
            }
            $avtactivite .= '</center></div>';

            $avtactivite .= '<script>
                        var canvas_devoir = "affichage-devoir-cours-'.$course->id.'";
                        var pourcentage_devoir_acheve_'.$course->id.' ='.$pcentdevoiracheve.';
                        var pourcentage_devoir_non_acheve_'.$course->id.' ='.$pcentdevoirnonacheve.';

                        creerPieDetaille(canvas_devoir, pourcentage_devoir_acheve_'.
                            $course->id.', pourcentage_devoir_non_acheve_'.$course->id.');
                        </script>';
        }

        if ($leconachevee + $leconnonachevee > 0) {
            $pcentleconachevee = $leconachevee / ($leconachevee + $leconnonachevee) * 100;
            $pcentleconnonachevee = 100 - $pcentleconachevee;
            $avtactivite .= '<div class = canvasDetaille>';
            $avtactivite .= '<center><b>'. get_string('lesson', 'block_tableau_bord') .'</b></center>';
            $avtactivite .= '<canvas id="affichage-lecon-cours-'. $course->id .'" height="60" width="80"></canvas>';

            // Affiche le nombre de lecons restantes.
            $avtactivite .= '<center>';
            if ($leconnonachevee <= 1) {
                $avtactivite .= get_string('oneactivityfeminineremaining', 'block_tableau_bord', $leconnonachevee);
            } else {
                $avtactivite .= get_string('someactivityfeminineremaining', 'block_tableau_bord', $leconnonachevee);
            }
            $avtactivite .= '</center></div>';
            $avtactivite .= '<script>
                        var canvas_lecon = "affichage-lecon-cours-'.$course->id.'";
                        var pourcentage_lecon_achevee_'.$course->id.' ='.$pcentleconachevee.';
                        var pourcentage_lecon_non_achevee_'.$course->id.' ='.$pcentleconnonachevee.';

                        creerPieDetaille(canvas_lecon, pourcentage_lecon_achevee_'.$course->id.
                        ', pourcentage_lecon_non_achevee_'.$course->id.');
                        </script>';
        }

        if ($ressourceachevee + $ressourcenonachevee > 0) {
            $pcentressourceachevee = $ressourceachevee / ($ressourceachevee + $ressourcenonachevee) * 100;
            $pcentressourcenonachevee = 100 - $pcentressourceachevee;
            $avtactivite .= '<div class = canvasDetaille>';
            $avtactivite .= '<center><b>'. get_string('resource', 'block_tableau_bord') .'</b></center>';
            $avtactivite .= '<canvas id="affichage-ressource-cours-' . $course->id . '" height="60" width="80"></canvas>';

            // Affiche le nombre de ressources restantes.
            $avtactivite .= '<center>';
            if ($ressourcenonachevee <= 1) {
                $avtactivite .= get_string('oneactivityfeminineremaining', 'block_tableau_bord', $ressourcenonachevee);
            } else {
                $avtactivite .= get_string('someactivityfeminineremaining', 'block_tableau_bord', $ressourcenonachevee);
            }
            $avtactivite .= '</center></div>';
            $avtactivite .= '<script>
                        var canvas_ressource = "affichage-ressource-cours-'.$course->id.'";
                        var pourcentage_ressource_achevee_'.$course->id.' ='.$pcentressourceachevee.';
                        var pourcentage_ressource_non_achevee_'.$course->id.' ='.$pcentressourcenonachevee.';

                        creerPieDetaille(canvas_ressource, pourcentage_ressource_achevee_'.$course->id.
                        ', pourcentage_ressource_non_achevee_'.$course->id.');
                        </script>';
        }

        if ($testacheve + $testnonacheve > 0) {
            $pcenttestacheve = $testacheve / ($testacheve + $testnonacheve) * 100;
            $pcenttestnonacheve = 100 - $pcenttestacheve;
            $avtactivite .= '<div class = canvasDetaille>';
            $avtactivite .= '<center><b>'. get_string('quiz', 'block_tableau_bord') .'</b></center>';
            $avtactivite .= '<canvas id="affichage-test-cours-'.$course->id.'" height="60" width="80"></canvas>';
            // Affiche le nombre de ressources restantes.
            $avtactivite .= '<center>';
            if ($testnonacheve <= 1) {
                $avtactivite .= get_string('oneactivityremaining', 'block_tableau_bord', $testnonacheve);
            } else {
                $avtactivite .= get_string('someactivityremaining', 'block_tableau_bord', $testnonacheve);
            }
            $avtactivite .= '</center></div>';

            $avtactivite .= '<script>
                        var canvas_test = "affichage-test-cours-'.$course->id.'";
                        var pourcentage_test_acheve_'.$course->id.' ='.$pcenttestacheve.';
                        var pourcentage_test_non_acheve_'.$course->id.' ='.$pcenttestnonacheve.';
                        creerPieDetaille(canvas_test, pourcentage_test_acheve_'. $course->id .
                        ', pourcentage_test_non_acheve_'. $course->id. ');</script>';
        }

        if ($autreacheve + $autrenonacheve > 0) {
            $pcentautreacheve = $autreacheve / ($autreacheve + $autrenonacheve) * 100;
            $pcentautrenonacheve = 100 - $pcentautreacheve;
            $avtactivite .= '<div class = canvasDetaille>';
            $avtactivite .= '<center><b>'. get_string('otheractivity', 'block_tableau_bord') .'</b></center>';
            $avtactivite .= '<canvas id="affichage-autre-cours-'. $course->id. '" height="60" width="80"></canvas>';
            $avtactivite .= '<center>';
            // Affiche le nombre d'autres activites restantes.
            if ($autrenonacheve <= 1) {
                $avtactivite .= get_string('oneactivityfeminineremaining', 'block_tableau_bord', $autrenonacheve);
            } else {
                $avtactivite .= get_string('someactivityfeminineremaining', 'block_tableau_bord', $autrenonacheve);
            }
            $avtactivite .= '</center></div>';

            $avtactivite .= '<script>
                        var canvas_autre = "affichage-autre-cours-'.$course->id.'";
                        var pourcentage_autre_acheve_'.$course->id.' ='.$pcentautreacheve.';
                        var pourcentage_autre_non_acheve_'.$course->id.' ='.$pcentautrenonacheve.';
                        creerPieDetaille(canvas_autre, pourcentage_autre_acheve_'. $course->id .
                        ', pourcentage_autre_non_acheve_'.$course->id.');</script>';
        }
        // Info bulle à la fin.
        $avtactivite .= $this->output->help_icon('studentdetailedprogress', 'block_tableau_bord');

        // Cree le diagramme d'avancement global.
        $avancementglobal .= ' <center><canvas id="affichage-global-cours-'. $course->id .
                    '" height="90" width="120" ></canvas></center>
                    <script>
                    var canvas = "affichage-global-cours-'.$course->id.'";
                    var pourcentage_act_achevee_'.$course->id.' ='.$pcentacheve.';
                    var pourcentage_act_non_achevee_'.$course->id.' ='.$pcentnonacheve.';
                    creerPie(canvas, pourcentage_act_achevee_'.$course->id.', pourcentage_act_non_achevee_'.$course->id.');
                    </script>

                    <center><b>'. get_string('studentprogress', 'block_tableau_bord').' : '.intval($pcentacheve).' %</b>
                    '.$this->output->help_icon('studentglobalprogress', 'block_tableau_bord').'</center>';

        // Contient les diagrammes d'avancement des types d'activite dans un menu deroulant.
        $avancementdetaille .= $this->collapsible_region(
                                        $avtactivite, 'region_detaille_'.$course->id,
                                        '<img src="'. $this->output->image_url('avancement_18', 'block_tableau_bord').
                                        '"alt="Avancement icon" /> <b>'.get_string('seeprogress', 'block_tableau_bord').'</b>');

        // Renvoie les deux types d'avancement separement.
        return array($avancementglobal, $avancementdetaille);
    }

    /**
     * Fonction qui cree et retourne le menu deroulant avec toutes les notifications pour un seul cours
     *     ainsi que leur bouton de suppression
     * Retourne une variable au format html
     */
    public function afficher_notification($course, $overviews) {
        global $USER, $DB, $CFG;

        $notifactivite = "";          // Contient toutes les notifications d'un type d'activite.
        $notifactivitecomplete = ""; // Contient toutes les notifications d'un type d'activite plus le titre.
        $html = '';                 // Contient toutes les notifications dans un menu deroulant.
        $nbtypeactivite = 0;       // Nombre de types d'activite different a afficher : correspond au nombre de notifications.

        // Pour chaque type d'activite du cours.
        foreach (array_keys($overviews[$course->id]) as $module) {
            $activiteaffichee = 0; // Nb activite qui sont affichees : au depart 0.
            $presencenotif = false;   // Booleen qui indique la presence de notification(s) affichee(s) ou non.

            // Pour chaque notification de ce type d'activite.
            foreach ($overviews[$course->id][$module] as $coursemodule => $notif) {
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

                $activiteaffichee ++; // Le nombre d'activite qui sont effectivement affichees.
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
                $icontext = '<img src="'. $this->output->image_url('notification_18', 'block_tableau_bord')
                            . '" alt="Notification icon" /> <b>'
                            . get_string('youhaveanotification', 'block_tableau_bord').'</b>';
            } else {
                $icontext = '<img src="'. $this->output->image_url('notification_18', 'block_tableau_bord')
                            . '" alt="Notification icon" /> <b>'
                            . get_string('youhavesomenotifications', 'block_tableau_bord', $nbtypeactivite).'</b>';
            }

            // Met la variable contenant toutes les notifications ainsi qu'un bouton d'aide dans un menu deroulant.
            $html .= $this->collapsible_region(
                                $notifactivitecomplete.$this->output->help_icon('notifications', 'block_tableau_bord'),
                                'region_notification_'.$course->id,
                                $icontext);
        }
        return $html;
    }
}
