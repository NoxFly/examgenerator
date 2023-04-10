<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


require_once(PATH_TEMPLATES . 'ApiController.php');
require_once(PATH_GUARDS . 'ApiPrivileges.guard.php');


class CourseParticipantsController extends ApiController {
    function __construct() {
        $this->guard = new ApiPrivilegesGuard([
            'GET' => UserType::ADMIN | UserType::TEACHER | UserType::STUDENT
        ]);
    }

    /**
     * Affiche la liste des étudiants participants à la matière demandée
     * pour l'année demandée.
     * 
     * Codes de retour possibles :
     * - 200 OK
     * - 401 Utilisateur non authentifié
     * 
     * @param stdClass $req
     * @param ApiSite $res
     */
    public function GET($req, $res) {
        $this->sendData($req, $res, 'getcourseParticipants');
    }


    /* -------------------------------------------------------- */


	protected function getcourseParticipants($req, $res) {
        $year = $req->query['year']?? $req->user->year;

        $this->data = $res->getDatabase()->query(
            'SELECT U.firstname, U.lastname, U.uuid
            FROM user U
                INNER JOIN student S ON (S.id_user = U.id)
                INNER JOIN courselevelyear CLY ON (CLY.id_level = S.id_level)
                INNER JOIN universityyear Y ON (Y.id = CLY.id_year)
            WHERE Y.year = :year
                AND Y.id_univ = :univId
                AND S.id_year = CLY.id_year
                AND CLY.id_course = :courseId',
            [
                'year' => $year,
                'univId' => $req->user->universityId,
                'courseId' => $req->params['courseId']
            ]
        )->fetchAll(PDO::FETCH_ASSOC);
	}
}