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


class CourseChaptersController extends ApiController {
    function __construct() {
        $this->guard = new ApiPrivilegesGuard([
            'GET'       => UserType::ADMIN | UserType::TEACHER | UserType::STUDENT,
			'POST'      => UserType::TEACHER,
			'PUT'       => UserType::TEACHER,
			'DELETE'    => UserType::TEACHER
        ]);
    }

    /**
     * Affiche la liste des chapitres pour un cours donné.
     * Si le cours n'existe pas, code 404
     * 
     * Codes de retour possibles :
     * - 200 OK
     * - 401 Utilisateur non authentifié
     * - 404 La matière n'existe pas
	 * - 500 Erreur pendant l'exécution de la requête dans la base de données
     * 
     * @param stdClass $req
     * @param ApiSite $res
     */
    public function GET($req, $res) {
        $this->sendData($req, $res, 'getCourseChapters');
    }

    /**
     * Permet à l'enseignant référent de modifier le chapitre voulu.
     * Si la matière ou le chapitre n'existent pas, code 404.
     * 
     * Codes de retour possibles :
     * - 200 OK
     * - 400 Champs manquants
     * - 401 Utilisateur non authentifié
     * - 403 N'a pas l'autorisation nécessaire pour modifier le chapitre
     * - 404 La matière ou le chapitre n'existent pas
	 * - 500 Erreur pendant l'exécution de la requête dans la base de données
     * 
     * @param stdClass $req
     * @param ApiSite $res
     */
    public function POST($req, $res) {
        $this->sendData($req, $res, 'modifyCourseChapter');
    }

    /**
     * Permet à l'enseignant référent de créer un chapitre dans sa matière.
     * Si la matière n'existe pas, code 404.
     * 
     * Codes de retour possibles :
     * - 200 OK
     * - 401 Utilisateur non authentifié
     * - 403 N'a pas l'autorisation nécessaire pour créer un chapitre
     * - 404 La matière n'existe pas
	 * - 500 Erreur pendant l'exécution de la requête dans la base de données
     * 
     * @param stdClass $req
     * @param ApiSite $res
     */
    public function PUT($req, $res) {
        $this->sendData($req, $res, 'createCourseChapter');
    }

    /**
     * Permet à l'enseignant référent de supprimer un chapitre de sa matière.
     * Si la matière ou le chapitre n'existent pas, code 404.
     * 
     * Codes de retour possibles :
     * - 200 OK
     * - 401 Utilisateur non authentifié
     * - 403 N'a pas l'autorisation nécessaire pour créer un chapitre
     * - 404 La matière ou le chapitre n'existent pas
	 * - 500 Erreur pendant l'exécution de la requête dans la base de données
     * 
     * @param stdClass $req
     * @param ApiSite $res
     */
    public function DELETE($req, $res) {
        $this->sendData($req, $res, 'deleteCourseChapter');
    }


    /* -------------------------------------------------------- */


    protected function getCourseChapters($req, $res) {
        $r = $res->getDatabase()->query(
            'SELECT id
            FROM course
            WHERE id = :courseId
                AND id_univ = :univId',
            [
                'univId' => $req->user->universityId,
                'courseId' => $req->params['courseId']
            ]
        )->fetch();

        if(!$r) {
            throw new ApiException(404);
        }
        
        $this->data = $res->getDatabase()->query(
            'SELECT id, label, position
            FROM coursechapter
            WHERE id_course = :courseId
            ORDER BY position ASC', 
            [
                'courseId' => $req->params['courseId']
            ]
        )->fetchAll(PDO::FETCH_ASSOC);
	}

    protected function modifyCourseChapter($req, $res) {
        $courseId = $req->params['courseId'];
        $chapterId = $req->params['chapterId'];
        $univId = $req->user->universityId;

        $r = $res->getDatabase()->query(
            'SELECT TT.id_teacher as teacherId
            FROM coursechapter CC
                INNER JOIN course C ON (C.id = CC.id_course)
                LEFT JOIN teacherteaching TT ON (TT.id_course = C.id)
                LEFT JOIN universityyear Y ON (Y.id = TT.id_year)
            WHERE C.id_univ = :univId
                AND C.id = :courseId
                AND CC.id = :chapterId
                AND Y.year = :year',
            [
                'univId' => $univId,
                'chapterId' => $chapterId,
                'courseId' => $courseId,
                'year' => $req->user->year
            ]
        )->fetch();

        if(!$r) {
            throw new ApiException(404);
        }

        if($r['teacherId'] !== $req->user->teacherId) {
            throw new ApiException(403);
        }

        $name = $req->body['name']?? NULL;

        if(!$name) {
            throw new ApiException(400, 'Missing fields');
        }

        $res->getDatabase()->query(
            'UPDATE coursechapter
                SET label = :name
                WHERE id_course = :courseId 
                AND id = :chapterId',
            [
                'courseId'  => $courseId,
                'chapterId' => $chapterId,
                'name' => $name
            ]
        );
    }

    protected function createCourseChapter($req, $res) {
        $courseId = $req->params['courseId'];
        $univId = $req->user->universityId;

        $r = $res->getDatabase()->query(
            'SELECT TT.id_teacher as teacherId
            FROM course C
                LEFT JOIN teacherteaching TT ON (TT.id_course = C.id)
                LEFT JOIN universityyear Y ON (Y.id = TT.id_year)
            WHERE C.id_univ = :univId
                AND C.id = :courseId
                AND Y.year = :year',
            [
                'univId' => $univId,
                'courseId' => $courseId,
                'year' => $req->user->year
            ]
        )->fetch();

        if(!$r) {
            throw new ApiException(404);
        }

        if($r['teacherId'] !== $req->user->teacherId) {
            throw new ApiException(403);
        }

        if(!isset($req->body['name'])) {
            throw new ApiException(400, 'Missing fields');
        }

        $name = trim($req->body['name']);

        if(mb_strlen($name) === 0) {
			throw new ApiException(400);
		}

        $lastPos = $res->getDatabase()->query(
            'SELECT MAX(CC.position) as position
            FROM coursechapter CC
            INNER JOIN course C ON (C.id = CC.id_course)
            WHERE CC.id_course = :courseId
                AND C.id_univ = :univId',
            [
                'univId' => $req->user->universityId,
                'courseId' => $req->params['courseId']
            ]
        )->fetchColumn();

        // should not occur but to be sure
        if(!$lastPos) {
            throw new ApiException(500);
        }

        // no chapters yet
        if($lastPos === NULL) {
            $lastPos = 0;
        }
        else
        {
            $lastPos++;
        }

        $res->getDatabase()->query(
            'INSERT INTO coursechapter (id_course, label, position)
                VALUES (:courseId, :label, :position)',
            [
                'courseId' => $req->params['courseId'],
                'label'    => $req->body['name'],
                'position' => $lastPos
            ]
        );

        $this->data['chapterId'] = $res->getDatabase()->getLastInsertedId();
    }

    protected function deleteCourseChapter($req, $res) {
        $chapter = $res->getDatabase()->query(
            'SELECT TT.id_teacher as teacherId, CC.position
            FROM course C
                LEFT JOIN coursechapter CC ON (CC.id_course = C.id)
                LEFT JOIN teacherteaching TT ON (TT.id_course = C.id)
                LEFT JOIN universityyear Y ON (Y.id = TT.id_year)
            WHERE C.id_univ = :univId
                AND C.id = :courseId
                AND CC.id = :chapterId
                AND Y.year = :year',
            [
                'univId' => $req->user->universityId,
                'courseId' => $req->params['courseId'],
                'chapterId' => $req->params['chapterId'],
                'year' => $req->user->year
            ]
        )->fetch();

        if(!$chapter) {
            throw new ApiException(404);
        }

        if($chapter['teacherId'] !== $req->user->teacherId) {
            throw new ApiException(403);
        }

        $res->getDatabase()->query(
            'DELETE FROM coursechapter
            WHERE id = :chapterId
                AND id_course = :courseId',
            [
                'courseId'  => $req->params['courseId'],
                'chapterId' => $req->params['chapterId']
            ]
        );

        $res->getDatabase()->query(
            'UPDATE coursechapter
            SET position = position - 1
            WHERE id = :chapterId
                AND position > :position',
            [
                'chapterId' => $req->params['chapterId'],
                'position' => $chapter['position']
            ]
        );
    }
}