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


class TeacherCoursesController extends ApiController {
	function __construct() {
        $this->guard = new ApiPrivilegesGuard([
            'GET' => UserType::ADMIN | UserType::TEACHER
        ]);
    }

	/**
     * Renvoie la liste des matières pour un enseignant donné (public).
     * @param stdClass $req
     * @param ApiSite $res
	 * 
	 * Codes de retour possibles :
     * - 200 OK
	 * - 401 Utilisateur non authentifié
	 * 
	 * @param stdClass $req
	 * @param ApiSite $res
     */
	public function GET($req, $res) {
		$this->sendData($req, $res, 'getCourses');
	}


    /* -------------------------------------------------------- */


	protected function getCourses($req, $res) {
		$sort = $req->query['sort']?? 'ASC';

		if($sort !== 'ASC' && $sort !== 'DESC') {
			$sort = 'ASC';
		}

		$this->data = $res->getDatabase()->query(
			'SELECT C.name, C.id, Y.year
			FROM course C
				INNER JOIN teacherteaching TT ON (TT.id_course = C.id)
				INNER JOIN universityyear Y ON (Y.id = TT.id_year)
			WHERE TT.id_teacher = :teacherId
				AND C.id_univ = :univId
			ORDER BY Y.year ' . $sort,
			[
				'univId' => $req->user->universityId,
				'teacherId' => $req->params['teacherId']
			]
		)->fetchAll(PDO::FETCH_ASSOC);
	}

}