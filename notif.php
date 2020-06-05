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

defined('MOODLE_INTERNAL') || die();

/**
 * Renvoie les notifs des cours pour un type d'activite sous la forme
 * clé - valeur : id_course_module - code_html_module.
 */
function creer_notif($courses, &$notification, $mod) {
    global $USER, $CFG, $DB;
    if ($mod == "assign") {
        if (empty($courses) || !is_array($courses) || count($courses) == 0) {
            return array();
        }
        // Renvoie tous les devoirs (que les visibles pour un etudiant).
        if (!$assignments = get_all_instances_in_courses('assign', $courses)) {
            return;
        }
        $assignmentids = array();
        $assignmentdate = array();
        // Boucle pour recuperer les id des devoirs qui necessitent une notification.
        foreach ($assignments as $key => $assignment) {
            // Ajoute le devoir a la table qui contient les informations concernant la suppression des notifications.
            $ajout = new stdClass();
            $ajout->id_user = $USER->id;
            $ajout->id_course_module = $assignment->coursemodule;
            $ajout->time_delete = '0';

            $paramnotif = array('id_user' => $USER->id, 'id_course_module' => $assignment->coursemodule );
            $notif = $DB->get_record('tdb_delete_notifications', $paramnotif);
            // On ajoute ce devoir a la table s'il n'y est pas encore present.
            if ($notif == false) {
                // On l'ajoute a la table.
                $DB->insert_record('tdb_delete_notifications', $ajout);
            }

            $time = time();
            $estouvert = false;
            $datelimite = false;
            // S'il y a une date "a remettre jusqu'au" et/ou une date limite de rendu.
            if ($assignment->duedate || $assignment->cutoffdate) {
                $cutoffdate = false;
                $duedate = false;
                if ($assignment->cutoffdate) {
                    $cutoffdate = $assignment->cutoffdate;
                }
                if ($assignment->duedate) {
                    $duedate = $assignment->duedate;
                }

                // Parametre de recherche : l'id du devoir dans la table course_modules.
                $paramdevoir = array('id' => $assignment->coursemodule);
                // On recupere l'enregistrement du devoir dans la table course_modules.
                if (($devoirinfo = $DB->get_records('course_modules', $paramdevoir)) == true) {
                    // Visibilite (1 si visible, 0 si cache).
                    $visibilite = $devoirinfo[$assignment->coursemodule]->visible;
                }

                if ($visibilite) {
                    // S'il y a une date "a remettre jusqu'au".
                    if ($duedate) {
                        $estouvert = ($assignment->allowsubmissionsfromdate <= $time && $time <= $duedate);
                        $datelimite = intval($assignment->duedate); // Timestamp en secondes.
                    } else if ($cutoffdate) { // Sinon s'il y a une date limite.
                        $estouvert = ($assignment->allowsubmissionsfromdate <= $time && $time <= $cutoffdate);
                        $datelimite = intval($assignment->cutoffdate); // Timestamp en secondes.
                    }
                }
            }
            // Si les conditions d'affichage de la notifications sont respectees alors on ajoute le devoir aux tableaux.
            if ($estouvert) {
                $assignmentids[] = $assignment->id; // Tableau contenant les id des devoirs.
                $assignmentdate[$assignment->id] = $datelimite; // Tableau contenant la date limite d'affichage pour chaque devoir.
            }
        }

        // Si aucun devoir ne necessite de notification alors c'est termine.
        if (empty($assignmentids)) {
            return true;
        }

        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        // Recuperation de toutes les chaines de caracteres utiles a afficher.
        $strduedate = get_string('duedate', 'block_tableau_bord');
        $strcutoffdate = get_string('nosubmissionsacceptedafter', 'block_tableau_bord');
        $strnolatesubmissions = get_string('nolatesubmissions', 'block_tableau_bord');
        $strduedateno = get_string('duedateno', 'block_tableau_bord');
        $strgraded = get_string('graded', 'block_tableau_bord');
        $strnotgradedyet = get_string('notgradedyet', 'block_tableau_bord');
        $strnotsubmittedyet = get_string('notsubmittedyet', 'block_tableau_bord');
        $strsubmitted = get_string('submitted', 'block_tableau_bord');
        $strassignment = get_string('modulenameassign', 'block_tableau_bord');
        $strreviewed = get_string('reviewed', 'block_tableau_bord');

        // We do all possible database work here *outside* of the loop to ensure this scales.
        list($sqlassignmentids, $assignmentidparams) = $DB->get_in_or_equal($assignmentids);
        $mysubmissions = null;
        $unmarkedsubmissions = null;

        foreach ($assignments as $assignment) {

            // Si ce devoir ne necessite pas de notification on passe au suivant.
            if (!in_array($assignment->id, $assignmentids)) {
                continue;
            }

            // Recuperation des informations du devoir dans la table 'delete_notif'.
            $paramnotif = array('id_user' => $USER->id, 'id_course_module' => $assignment->coursemodule );
            $notif = $DB->get_record('tdb_delete_notifications', $paramnotif);

            $dimmedclass = '';
            if (!$assignment->visible) {
                $dimmedclass = ' class="dimmed"';
            }

            // Lien vers le devoir.
            $href = $CFG->wwwroot . '/mod/assign/view.php?id=' . $assignment->coursemodule;

            $str = ""; // Contient le code html de la notification.

            $context = context_module::instance($assignment->coursemodule);

            // Si l'utilisateur a le droit de noter (enseignant).
            if (has_capability('mod/assign:grade', $context)) {
                // Requete pour recuperer les devoirs rendus.
                $rs = $DB->get_recordset_sql('SELECT
                                                  s.assignment as assignment,
                                                  s.userid as userid,
                                                  s.id as id,
                                                  s.status as status,
                                                  g.timemodified as timegraded
                                              FROM {assign_submission} s
                                              LEFT JOIN {assign_grades} g ON
                                                  s.userid = g.userid AND
                                                  s.assignment = g.assignment
                                              WHERE
                                                  ( g.timemodified is NULL OR
                                                  s.timemodified > g.timemodified ) AND
                                                  s.timemodified > '. $notif->time_delete .' AND
                                                  s.timemodified IS NOT NULL AND
                                                  s.status = "submitted" AND
                                                  s.assignment = '. $assignment->id);

                $unmarkedsubmissions = array();
                // On recupere les ids de la table 'assign_submission' de chaque nouveau devoir pour chaque etudiant.
                foreach ($rs as $rd) {
                    $unmarkedsubmissions[$rd->assignment][$rd->userid] = $rd->id;
                }
                $rs->close();

                // Compte le nombre de devoirs a corriger depuis la derniere suppression manuelle de la notification
                // (depuis le debut si jamais de suppression).
                $submissions = 0;
                if ($students = get_enrolled_users($context, 'mod/assign:view', 0, 'u.id')) {
                    foreach ($students as $student) {
                        if (isset($unmarkedsubmissions[$assignment->id][$student->id])) {
                            $submissions++;
                        }
                    }
                }

                // S'il y a des devoirs a corriger on cree une notification.
                if ($submissions) {
                    $str = '<div class="assign overview" id="'.$assignment->coursemodule.'">' .
                           '<div class="name">' .
                           $strassignment . ': '.
                           '<a title="' . $strassignment . '" ' .
                               'href="' . $href . '">' .
                           format_string($assignment->name) .
                           '</a></div>';

                    $urlparams = array('id' => $assignment->coursemodule, 'action' => 'grading');
                    $url = new moodle_url('/mod/assign/view.php', $urlparams);
                    $str .= '<div class="details">' .
                            '<a href="' . $url . '">' .
                            get_string('submissionsnotgraded', 'block_tableau_bord', $submissions) .
                            '</a></div>';
                    $str .= '</div>';
                }
            }
            // Si l'utilisateur a le droit de soumettre un devoir (etudiant).
            if (has_capability('mod/assign:submit', $context)) {
                // Recuperation de l'etat du devoir de l'utilisateur.
                $paramdevoir = array('userid' => $USER->id, 'assignment' => $assignment->id );
                $devoirrendu = $DB->get_record('assign_submission', $paramdevoir);
                // Si l'utilisateur a rendu un devoir on n'affiche pas de notification.
                if ($devoirrendu != null) {
                    if ($devoirrendu->status == "submitted") {
                        continue;
                    }
                }
                // Si l'utilisateur a supprime la notification on n'affiche pas de notification.
                if ($notif->time_delete != 0) {
                    continue;
                }
                // Construction de la notification.
                $str = '<div class="assign overview" id="'.$assignment->coursemodule.'">' .
                       '<div class="name">' .
                       $strassignment . ': '.
                       '<a ' . $dimmedclass .
                           'title="' . $strassignment . '" ' .
                           'href="' . $href . '">' .
                       format_string($assignment->name) .
                       '</a></div>';

                // Calcul de l'intervalle de temps qui reste avant la date de remise.
                // Recuperation de la date limite (date de rendu en priorite sinon date limite d'ouverture).
                $datelimite = $assignmentdate[$assignment->id];
                $aujourdhui = intval(time());
                $intervallesecondes = intval($datelimite - $aujourdhui);
                $intervalleformate = format_time($intervallesecondes); // Formate un temps en secondes en un temps en M-J-H-m.
                $jours = intval($intervallesecondes / (3600 * 24));

                // Texte en rouge quand urgence ( <7 jours).
                if ($jours < 7) {
                    $str .= '<div class="info" style="color:red;">';
                } else {
                    $str .= '<div class="info">';
                }
                $str .= 'Temps restant : ' .$intervalleformate. '</div>';

                // S'il y a une date limite on affiche qu'aucun devoir en retard ne peut etre accepte
                // ou alors on affiche cette date limite.
                if ($assignment->cutoffdate) {
                    if ($assignment->cutoffdate == $assignment->duedate) {
                        $str .= '<div class="info">' . $strnolatesubmissions . '</div>';
                    } else {
                        $userdate = userdate($assignment->cutoffdate);
                        $str .= '<div class="info">' . $strcutoffdate . ': ' . $userdate . '</div>';
                    }
                }

                $str .= '</div>';
            }

            // S'il y a une notification a ajouter.
            if (!empty($str)) {
                // S'il n'y a pas encore de notification de devoirs pour ce cours.
                if (empty($notification[$assignment->course]['assign'])) {
                    $notification[$assignment->course]['assign'] = array();
                    $notification[$assignment->course]['assign'][$assignment->coursemodule] = $str;
                } else {
                    $notification[$assignment->course]['assign'][$assignment->coursemodule] = $str;
                }
            }
        }
    }

    if ($mod == "forum") {
        if (empty($courses) || !is_array($courses) || count($courses) == 0) {
            return array();
        }
        // Renvoie tous les forums (que les visibiles pour les etudiants).
        if (!$forums = get_all_instances_in_courses('forum', $courses)) {
            return;
        }

        // Courses to search for new posts.
        $coursessqls = array();
        $params = array();
        $tableauaccesforum = array();

        // Recupere les dates de dernier acces de l'utilisateur pour chaque forum.
        foreach ($forums as $forum) {
            $tableauaccesforum[$forum->id] = 0;

            $param = array ('userid' => $USER->id, 'course' => $forum->course, 'module' => 'forum', 'cmid' => $forum->coursemodule);

            $infos = $DB->get_records('log', $param);
            $date = 0;
            if (!empty($infos)) {
                foreach ($infos as $info) {
                    if ($info->time > $date) {
                        $date = $info->time;
                    }
                }
                if ($date > $tableauaccesforum[$forum->id]) {
                    $tableauaccesforum[$forum->id] = $date;
                }
            }
        }

        $strforum = get_string('modulenameforum', 'block_tableau_bord');
        foreach ($forums as $forum) {
            // Ajoute le forum a la table qui contient les informations concernant la suppression des notifications.
            $ajout = new stdClass();
            $ajout->id_user = $USER->id;
            $ajout->id_course_module = $forum->coursemodule;
            $ajout->time_delete = '0';
            $paramnotif = array('id_user' => $USER->id, 'id_course_module' => $forum->coursemodule );
            $notif = $DB->get_record('tdb_delete_notifications', $paramnotif);

            // Si ce forum n'est pas presente alors on l'ajoute a la table.
            if ($notif == false) {
                // On l'ajoute a la table.
                $DB->insert_record('tdb_delete_notifications', $ajout);
                // On la recupere.
                $notif = $DB->get_record('tdb_delete_notifications', $paramnotif);
            }

            // Remise a zero des parametres de recherche de nouveaux messages.
            unset($params);
            $params = array();

            $coursessql = '(f.id = ? AND p.created > ?)';
            $params[] = $forum->id;

            // Si notification jamais supprimee on se base sur la date de dernier acces.
            if ($notif->time_delete == 0) {
                $params[] = $tableauaccesforum[$forum->id];
            } else {
                // Si la date de suppression > a la date de dernier acces on se base dessus pour les nouveaux messages.
                if ($notif->time_delete > $tableauaccesforum[$forum->id]) {
                    $params[] = $notif->time_delete;
                } else {
                    $params[] = $tableauaccesforum[$forum->id];
                }
            }

            $params[] = $USER->id;
            // Requete sql permettant de recuperer le nombre de nouveaux messages pour le forum.
            $sql = "SELECT f.id, COUNT(*) as count "
                    .'FROM {forum} f '
                    .'JOIN {forum_discussions} d ON d.forum  = f.id '
                    .'JOIN {forum_posts} p ON p.discussion = d.id '
                    ."WHERE ($coursessql) "
                    .'AND p.userid != ? '
                    .'GROUP BY f.id';

            // Recupere un tableau ayant pour cle l'id du forum (course_modules) et contenant cet id
            // Ainsi que le nombre de nouveaux messages.
            if (!$new = $DB->get_records_sql($sql, $params)) {
                $new = array();
            }

            // Si la variable $new n'est pas vide alors il y a au moins un nouveau message dans le forum.
            if (!empty($new)) {
                // Parametre de recherche : l'id du forum dans la table course_modules.
                $parametreforum = array('id' => $forum->coursemodule);
                // On recupere l'enregistrement du forum dans la table course_modules.
                if (($foruminfo = $DB->get_records('course_modules', $parametreforum)) == true) {
                    $visibilite = $foruminfo[$forum->coursemodule]->visible;
                }

                // Si le forum n'est pas cache et que la disponibilite du forum n'est pas restreinte on cree la notification.
                if ($visibilite) {
                    $str = '';
                    // Recupere le nombre de nouveaux messages du forum.
                    $count = $new[$forum->id]->count;
                    // S'il y a au moins un nouveau message alors on cree une notification (condition pas utile normalement).
                    if ($count > 0) {
                        $str .= '<div class="overview forum"><div class="name">' . $strforum .
                                ': <a title="' . $strforum . '" href="' . $CFG->wwwroot . '/mod/forum/view.php?f=' . $forum->id .
                                '">' . $forum->name.'</a></div>';
                        $str .= '<div class="info"><span class="postsincelogin">';
                        $str .= get_string('overviewnumpostssince', 'block_tableau_bord', $count)."</span>";

                        $str .= '</div></div>';
                    }

                    // S'il y a bien une notification on l'ajoute a la table contenant les autres notifications.
                    if (!empty($str)) {
                        if (empty($notification[$forum->course]['forum'])) {
                            $notification[$forum->course]['forum'] = array();
                            $notification[$forum->course]['forum'][$forum->coursemodule] = $str;
                        } else {
                            $notification[$forum->course]['forum'][$forum->coursemodule] = $str;
                        }
                    }
                }
            }
        }
    }

    if ($mod == "journal") {
        if (!get_config('journal', 'overview')) {
            return array();
        }

        if (empty($courses) || !is_array($courses) || count($courses) == 0) {
            return array();
        }

        if (!$journals = get_all_instances_in_courses('journal', $courses)) {
            return array();
        }

        $strjournal = get_string('modulenamejournal', 'block_tableau_bord');

        $params = array();
        $timenow = time();
        foreach ($journals as $journal) {
            // Ajoute le journal a la table qui contient les informations concernant la suppression des notifications.
            $ajout = new stdClass();
            $ajout->id_user = $USER->id;
            $ajout->id_course_module = $journal->coursemodule;
            $ajout->time_delete = '0';

            $paramnotif = array('id_user' => $USER->id, 'id_course_module' => $journal->coursemodule);
            $notif = $DB->get_record('tdb_delete_notifications', $paramnotif);
            // On ajoute ce journal a la table s'il n'y est pas encore present.
            if ($notif == false) {
                $DB->insert_record('tdb_delete_notifications', $ajout);
                $notif = $DB->get_record('tdb_delete_notifications', $paramnotif);
            }

            $courses[$journal->course]->format = $DB->get_field('course', 'format', array('id' => $journal->course));
            // Si le cours a pour format "weeks" et que le journal a une duree limitee alors on regarde s'il est toujours ouvert.
            // Sinon il l'est toujours.
            if ($courses[$journal->course]->format == 'weeks' AND $journal->days) {
                // Date de debut du cours.
                $coursestartdate = $courses[$journal->course]->startdate;
                // Date de debut du journal -> correspond a la date du debut de la section
                // (= date de debut du cours + un nombre de semaine egal au numero de section).
                $journal->timestart  = $coursestartdate + (($journal->section - 1) * 608400);
                $journal->timefinish = 9999999999;
                if (!empty($journal->days)) {
                    $journal->timefinish = $journal->timestart + (3600 * 24 * $journal->days);
                }

                // Parametre de recherche : l'id du journal dans la table course_modules.
                $paramejournal = array('id' => $journal->coursemodule);
                // On recupere l'enregistrement du journal dans la table course_modules.
                if (($journalinfo = $DB->get_records('course_modules', $paramejournal)) == true) {
                    // Visibilite (1 si visible, 0 si cache).
                    $visibilite = $journalinfo[$journal->coursemodule]->visible;
                }

                // Si le journal n'est pas cache.
                // (inutile dans le cas ou l'utilisateur est un etudiant car la fonction
                // 'get_all_instances_in_courses' ne lui retourne pas les journaux caches).
                if ($visible) {
                    // S'il a une date de fin de restriction.
                    $journalopen = ($journal->timestart < $timenow && $timenow < $journal->timefinish);
                }
            } else {
                $journalopen = true;
            }

            // Parametres pour rechercher les infos de l'utilisateur concernant le journal.
            $paramutilisateur = array('userid' => $USER->id, 'cmid' => $journal->coursemodule);
            $datedernieracces = 0;
            // Recuperation de la date de dernier acces de l'utilisateur.
            if (($utilisateurinfo = $DB->get_records('log', $paramutilisateur)) == true) {
                foreach ($utilisateurinfo as $info) {
                    if ($info->time > $datedernieracces) {
                        $datedernieracces = $info->time;
                    }
                }
            }
            $datenouveaupost = 0;
            // Si la date de dernier acces est posterieure a la suppression manuelle de la notification.
            if ($datedernieracces >= $notif->time_delete) {
                $datenouveaupost = $datedernieracces;
            } else {
                $datenouveaupost = $notif->time_delete;
            }

            $str = "";
            // Si le journal est ouvert alors la notification est cree.
            if ($journalopen) {
                $context = context_module::instance($journal->coursemodule);

                // Si l'utilisateur a le droit d'ajouter un commentaire (enseignant).
                if (has_capability('mod/journal:manageentries', $context)) {
                    // Requete sql pour compter le nombre de nouvelles modifications dans le journal.
                    $sql = 'SELECT j.journal, COUNT(*) as count
                                        FROM {journal_entries} j
                                        WHERE j.journal = ? AND j.modified > ?
                                        AND j.userid != ? ';

                    // Parametres pour la requete.
                    unset($params);
                    $params = array();
                    $params[] = $journal->id;
                    $params[] = $datenouveaupost;
                    $params[] = $USER->id;
                    // Recupere un tableau ayant pour cle l'id du journal (table journal)
                    // et contenant cet id ainsi que le nombre de nouveaux messages.
                    $new = $DB->get_records_sql($sql, $params);
                    // Si la cle ayant pour valeur l'id du journal existe alors il y a de nouvelles modifs de journal.
                    if (array_key_exists($journal->id, $new)) {
                        $str = '<div class="journal overview" id="'.$journal->coursemodule.'">
                                    <div class="name">'.
                                       $strjournal.': <a '.($journal->visible ? '' : ' class="dimmed"').
                                       ' href="'.$CFG->wwwroot.'/mod/journal/view.php?id='.$journal->coursemodule.'">'.
                                       $journal->name.'</a>
                                     </div>
                                     <div class="detail">
                                        Vous avez '.$new[$journal->id]->count.' nouveaux posts
                                    </div>
                                </div>';
                    }

                } else if (has_capability('mod/journal:addentries', $context)) { // S'il peut ecrire dedans (etudiant)
                    // Requete qui permet de recuperer l'enregistrement dans la table pour le journal de l'utilisateur
                    // si il y a eu une nouvelle note depuis la derniere fois.
                    $sql = 'SELECT j.journal
                            FROM {journal_entries} j
                            WHERE j.journal = ? AND j.timemarked > ?
                            AND j.userid = ? ';
                    unset($params);
                    $params = array();
                    $params[] = $journal->id;
                    $params[] = $datenouveaupost;
                    $params[] = $USER->id;

                    $new = $DB->get_records_sql($sql, $params);
                    // Si la cle ayant pour valeur l'id du journal existe alors il y a eu un nouveau commentaire de l'enseignant.
                    if (array_key_exists($journal->id, $new)) {
                        $str = '<div class="journal overview" id="'.$journal->coursemodule.'">
                                    <div class="name">'.
                                       $strjournal.': <a '.($journal->visible ? '' : ' class="dimmed"').
                                       ' href="'.$CFG->wwwroot.'/mod/journal/view.php?id='.$journal->coursemodule.'">'.
                                       $journal->name.'</a>
                                    </div>
                                    <div class="detail">
                                        Vous avez un nouveau commentaire de l\'enseignant.
                                    </div>
                                </div>';
                    }
                }

                if (!empty($str)) {
                    if (empty($notification[$journal->course]['journal'])) {
                        $notification[$journal->course]['journal'] = array();
                        $notification[$journal->course]['journal'][$journal->coursemodule] = $str;
                    } else {
                        $notification[$journal->course]['journal'][$journal->coursemodule] = $str;
                    }
                }
            }
        }
    }

    if ($mod == "quiz") {
        if (empty($courses) || !is_array($courses) || count($courses) == 0) {
            return array();
        }

        // Recupere toutes les instances de tests (que les visibles pour un etudiant).
        if (!$quizzes = get_all_instances_in_courses('quiz', $courses)) {
            return;
        }

        // Recupere des phrases dans le fichier langue.
        $strquiz = get_string('modulenamequiz', 'block_tableau_bord');
        $strnoattempts = get_string('noattempts', 'block_tableau_bord');

        $now = time();
        foreach ($quizzes as $quiz) {
            $str = "";

            // Ajoute le test a la table qui contient les informations concernant la suppression des notifications.
            $ajout = new stdClass();
            $ajout->id_user = $USER->id;
            $ajout->id_course_module = $quiz->coursemodule;
            $ajout->time_delete = '0';

            $paramnotif = array('id_user' => $USER->id, 'id_course_module' => $quiz->coursemodule );
            $notif = $DB->get_record('tdb_delete_notifications', $paramnotif);
            // On ajoute ce test a la table s'il n'y est pas encore present.
            if ($notif == false) {
                $DB->insert_record('tdb_delete_notifications', $ajout);
                $notif = $DB->get_record('tdb_delete_notifications', $paramnotif);
            }

            // Parametre de recherche : l'id du quiz dans la table course_modules.
            $paramquiz = array('id' => $quiz->coursemodule);
            // On recupere l'enregistrement du quiz dans la table course_modules.
            if (($quizinfo = $DB->get_records('course_modules', $paramquiz)) == true) {
                // Visibilite (1 si visible, 0 si cache)
                // ->utile que pour un utilisateur enseignant car pour l'etudiant cette condition
                // est deja utilisee dans la fonction get_all_instances_in_courses.
                $visibilite = $quizinfo[$quiz->coursemodule]->visible;
                // Si les restrictions empechent l'acces au quizz alors on n'affiche pas de notification.
                if (!$visibilite) {
                    continue;
                }
            }
            // Si le quiz est ouvert.
            if ($quiz->timeclose >= $now && $quiz->timeopen < $now ) {
                $context = context_module::instance($quiz->coursemodule);

                // Si l'utilisateur a le droit de voir les rapports (enseignant).
                if (has_capability('mod/quiz:viewreports', $context)) {
                    $paramquiz = array('quiz' => $quiz->id);
                    $paramenseignant = array ('userid' => $USER->id, 'cmid' => $quiz->coursemodule);
                    $dernieracces = 0;
                    // Recuperation de la date de dernier acces de l'enseignant.
                    if (($enseignantinfo = $DB->get_records('log', $paramenseignant)) == true) {
                        foreach ($enseignantinfo as $info) {
                            if ($info->time > $dernieracces) {
                                $dernieracces = $info->time;
                            }
                        }
                    }

                    $nombrenouveautest = 0;
                    if (($quizinfo = $DB->get_records('quiz_attempts', $paramquiz)) == true) {
                        foreach ($quizinfo as $q) {
                            // S'il y a eu un acces au test depuis la suppression de la notification.
                            if ($dernieracces >= $notif->time_delete) {
                                // Si la tentative a eu lieu depuis ce dernier acces.
                                if ($q->timefinish > $dernieracces) {
                                    $nombrenouveautest++;
                                }
                            } else {
                                // Sinon si la tentative a eu lieu apres la suppression de la notification.
                                if ($q->timefinish > $notif->time_delete) {
                                    $nombrenouveautest++;
                                }
                            }
                        }
                    }

                    // S'il y a au moins une nouvelle tentative on cree la notification.
                    if ($nombrenouveautest > 0) {
                        $str = '<div class="quiz overview">' .
                                '<div class="name">' . $strquiz . ': <a ' .
                                ($quiz->visible ? '' : ' class="dimmed"') .
                                ' href="' . $CFG->wwwroot . '/mod/quiz/view.php?id=' .
                                $quiz->coursemodule . '">' .
                                $quiz->name . '</a></div>';
                        $str .= '<div class="info">' . get_string('quizcloseson', 'block_tableau_bord',
                                userdate($quiz->timeclose)) . '</div>';
                        $str .= '<div class="info">';
                        if ($nombrenouveautest > 1) {
                            $str .= get_string('youhavesomequizzes', 'block_tableau_bord', $nombrenouveautest);
                        } else {
                            $str .= get_string('youhaveaquizz', 'block_tableau_bord', $nombrenouveautest);
                        }
                        $str .= '</div></div>';
                    }
                } else if (has_any_capability(array('mod/quiz:reviewmyattempts', 'mod/quiz:attempt'), $context)) { // Etudiant.
                    if (isset($USER->id)) {
                        $attempts = quiz_get_user_attempts($quiz->id, $USER->id);

                        $nbattempts = count($attempts); // Nombre de tentatives.
                        // Creation d'une notification si pas encore de tentative et pas supprimee.
                        if ($nbattempts == 0 && $notif->time_delete == 0 ) {
                            // Creation du lien vers le test.
                            $str = '<div class="quiz overview">' .
                                    '<div class="name">' . $strquiz . ': <a ' .
                                    ($quiz->visible ? '' : ' class="dimmed"') .
                                    ' href="' . $CFG->wwwroot . '/mod/quiz/view.php?id=' .
                                    $quiz->coursemodule . '">' .
                                    $quiz->name . '</a></div>';

                            // Calcul du temps restant.
                            $datelimite = $quiz->timeclose; // Recuperation de la date limite (de rendu sinon d'ouverture).
                            $aujourdhui = $now;
                            $intervallesecondes = intval($datelimite - $aujourdhui);
                            $intervalleformate = format_time($intervallesecondes); // Intervalle formate en J-H-m.
                            $jours = intval($intervallesecondes / (3600 * 24));

                            // Texte en rouge quand urgence < 7 jours.
                            if ($jours < 7) {
                                $str .= '<div class="info" style="color:red;">';
                            } else {
                                $str .= '<div class="info">';
                            }
                            // Creation de la notification.
                            $str .= 'Temps restant : ' . $intervalleformate . '</div>';
                            $str .= '<div class="info">' . get_string('quizcloseson', 'quiz', userdate($quiz->timeclose)) .
                                    '</div>';
                            $str .= '<div class="info"> ' . $strnoattempts . '</div>';
                            $str .= '</div>';
                        }
                    }
                } else {
                    continue;
                }

                // S'il y a une notification on l'ajoute au tableau.
                if (!empty($str)) {
                    if (empty($notification[$quiz->course]['quiz'])) {
                        $notification[$quiz->course]['quiz'] = array();
                        $notification[$quiz->course]['quiz'][$quiz->coursemodule] = $str;
                    } else {
                        $notification[$quiz->course]['quiz'][$quiz->coursemodule] = $str;
                    }
                }
            }
        }
    }
}