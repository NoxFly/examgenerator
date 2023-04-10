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


class CourseReferentsController extends ApiController {
    function __construct() {
        $this->guard = new ApiPrivilegesGuard([
            'GET'       => UserType::ADMIN | UserType::TEACHER | UserType::STUDENT,
			'POST'      => UserType::ADMIN,
			'DELETE'    => UserType::ADMIN
        ]);
    }

    /**
     * Affiche le nom, prénom, numéro de l'enseignant référent de la matière demandée
     * pour l'année demandée.
     * Si aucun enseignant n'est encore assigné, renvoie NULL pour tous les champs.
     * 
     * Codes de retour possibles :
     * - 200 OK
     * - 401 Utilisateur non authentifié
     * 
     * @param stdClass $req
     * @param ApiSite $res
     */
    public function GET($req, $res) {
        $this->sendData($req, $res, 'getReferent');
    }

    /**
     * Assigne pour la première fois ou modifier l'enseignant référent à la matière
     * demandée, pour l'année demandée.
     * 
     * Codes de retour possibles :
     * - 200 OK
     * - 400 Champs manquants
     * - 401 Utilisateur non authentifié
     * - 404 La matière ou l'enseignant n'existent pas
	 * - 500 Erreur pendant l'exécution de la requête dans la base de données
     * 
     * @param stdClass $req
     * @param ApiSite $res
     */
	public function POST($req, $res) {
		$this->sendData($req, $res, 'putOrModifyReferent');
	}

    /**
     * Enlève la référence d'un enseignant à une matière.
     * 
     * Codes de retour possibles :
     * - 200 OK
     * - 401 Utilisateur non authentifié
     * - 404 La matière ou l'enseignant n'existent pas
	 * - 500 Erreur pendant l'exécution de la requête dans la base de données
     * 
     * @param stdClass $req
     * @param ApiSite $res
     */
    public function DELETE($req, $res) {
		$this->sendData($req, $res, 'deleteReferent');
	}


    /* -------------------------------------------------------- */


	protected function getReferent($req, $res) {
        $year = $req->query['year']?? $req->user->year;

        $data = $res->getDatabase()->query(
            'SELECT U.firstname, U.lastname, U.uuid, T.id as teacherId, T.id_user as userId
            FROM user U
                INNER JOIN teacher T ON (T.id_user = U.id)
                INNER JOIN teacherteaching TT ON (TT.id_teacher = T.id)
                INNER JOIN universityyear Y ON (Y.id = TT.id_year)
            WHERE Y.year = :year
                AND Y.id_univ = :univId
                AND TT.id_course = :courseId',
            [
                'year' => $year,
                'univId' => $req->user->universityId,
                'courseId' => $req->params['courseId']
            ]
        )->fetchObject();

        if($data === false) {
            $data = (object)array(
                'firstname' => NULL,
                'lastname' => NULL,
                'uuid' => NULL
            );
        }

        $this->data = $data;
	}

	protected function putOrModifyReferent($req, $res) {
        $db = $res->getDatabase();

        $referentUserId = $req->body['referentId']?? NULL;

        if(!$referentUserId) {
            throw new ApiException(400);
        }

        $yearId = $this->getYearId($req, $res);

        $courseId = $req->params['courseId'];


        $course = $db->query(
            'SELECT 1
            FROM course
            WHERE id = :courseId
                AND id_univ = :univId',
            [
                'courseId' => $courseId,
                'univId' => $req->user->universityId
            ]
        )->fetch();

        if(!$course) {
            throw new ApiException(404);
        }

        $referent = $db->query(
            'SELECT TT.id_teacher as teacherId, TT.id_teacher as referentId, A.id_univ as univId
            FROM teacherteaching TT
                INNER JOIN teacher T ON (T.id = TT.id_teacher)
                INNER JOIN user U ON (U.id = T.id_user)
                INNER JOIN account A ON (A.id = U.id_account)
            WHERE TT.id_course = :courseId
                AND TT.id_year = :yearId',
            [
                'courseId' => $courseId,
                'yearId' => $yearId
            ]
        )->fetch(PDO::FETCH_ASSOC);

        if($referent && ($referent['univId'] !== $req->user->universityId)) {
            throw new ApiException(404);
        }


        $query = '';
        $params = [
            'teacherId' => $referentUserId,
            'courseId' => $req->params['courseId'],
            'yearId' => $yearId
        ];


        // PUT
        if(!$referent || !$referent['referentId']) {
            $query = 'INSERT INTO teacherteaching (id_teacher, id_course, id_year)
                VALUES (:teacherId, :courseId, :yearId)';
        }

        // POST
        else {
            $query = 'UPDATE teacherteaching
                SET id_teacher = :teacherId
                WHERE id_course = :courseId
                    AND id_year = :yearId';
        }

        $db->query($query, $params);
    }
    
    protected function deleteReferent($req, $res) {
        $yearId = $this->getYearId($req, $res);

        try {
            $res->getDatabase()->query(
                'DELETE FROM teacherteaching
                WHERE id_teacher = :teacherId
                    AND id_year = :yearId
                    AND id_course = :courseId',
                [
                    'teacherId' => $req->params['referentId'],
                    'courseId' => $req->params['courseId'],
                    'yearId' => $yearId
                ]
            );
        }
        catch(Exception $e) {
            throw new ApiException(404);
        }
    }
}