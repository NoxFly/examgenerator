<?php

/**
 * @copyright Copyrights (C) 2023 Arthur Gros All rights reserved.
 * @author Arthur Gros
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


require_once(PATH_ENGINE_TEMPLATES . 'Controller.php');
require_once(PATH_GUARDS . 'Privileges.guard.php');


class MyExamsController extends Controller {
    function __construct() {
        $this->guard = new PrivilegesGuard(
            UserType::TEACHER | UserType::STUDENT,
            UserType::NONE,
            UserType::NONE,
            UserType::NONE
        );
    }

    /**
     * @param stdClass $req
     * @param WebSite $req
     */
    public function GET($req, $res) {
        $exams = $res->api()->fetch('GET', 'exams/me');

        $data = [
            ExamState::COMING   => [],
            ExamState::PENDING  => [],
            ExamState::DONE     => [],
            ExamState::REVISED  => []
        ];

        foreach ($exams as $exam)
        {
            $sStart = $exam['dateStart'];
            $sEnd = $exam['dateEnd'];

            $dStart=null;
            $dStart = (new DateTime($sStart))->format('d/m/Y H:i');
            $dEnd = (new DateTime($sEnd))->format('d/m/Y H:i');
            
            $exam['dateStart'] = $dStart;
            $exam['dateEnd'] = $dEnd;
            $exam['name'] = ucfirst($exam['name']);

            array_push($data[$exam['status']], $exam);
        }

        if(bitwiseAND(UserType::STUDENT, $_SESSION['privileges'])) {
            unset($data[ExamState::COMING]);
        }

        $res->render('board/exam/examList', $data);
    }
}

/*
    - eleve doit voir exams en cours et pas encore fait
    - et voir aussi examen termine et corrige qu'il a passe et de cette année
    - enseignant: exams de cette année creer et pas commencer
        - pas encore commencer
        - fini (corrigé et à corrigé)
        - en cours

ETU :
- [examen de cette année]
    - qu'il doit passer et qu'il n'a pas encore répondu
    - qu'il a passé et qui sont corrigés
ENS :
- [examen de cette année qu'il a créé]
    - pas encore commencé
    - en cours
    - fini
        - pas encore corrigé
        - corrigé
*/