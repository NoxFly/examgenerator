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


class CourseExamsController extends ApiController {
    function __construct() {
        $this->guard = new ApiPrivilegesGuard([
            'GET' => UserType::ADMIN | UserType::TEACHER | UserType::STUDENT
        ]);
    }

    /**
     * Affiche la liste des examens pour la matière données, pour l'année donnée.
     * Si la matière n'existe pas, renvoie un tableau vide.
     * 
     * Codes de retour possibles :
     * - 200 OK
     * - 401 Utilisateur non authentifié
     * - 403 Utilisateur non autorisé
     * - 500 Erreur lors d'une requête dans la base
     * 
     * @param stdClass $req
     * @param ApiSite $res
     */
    public function GET($req, $res) {
        $this->sendData($req, $res, 'getCourseExams');
    }


    /* -------------------------------------------------------- */


	protected function getCourseExams($req, $res) {
        $year = $req->query['year']?? $req->user->year;

        $isStudent = bitwiseAND($req->user->privileges, UserType::STUDENT);
        $isTeacher = bitwiseAND($req->user->privileges, UserType::TEACHER);

        if ($isStudent)
        {
            $this->data = $res->getDatabase()->query(
                'SELECT DISTINCT E.id, E.name, E.coeff, E.type, E.date_start as dateStart, E.date_end as dateEnd, (CASE WHEN A.id_student IS NULL THEN false ELSE true END) as repondu
                FROM exam E
                    INNER JOIN universityyear Y ON (Y.id = E.id_year)
                    INNER JOIN examlevel EL ON (EL.id_exam = E.id)
                    INNER JOIN student S ON (S.id_level = EL.id_level)
                    INNER JOIN course C ON (C.id = E.id_course)
                    LEFT JOIN answer A ON (A.id_exam = E.id AND A.id_student = S.id)
                WHERE S.id = :studentId
                    AND Y.year = :year
                    AND C.id_univ = :univId
                    AND C.id = :courseId
                    AND E.date_start <= :start
                    AND E.date_end >= :end
                ORDER BY dateEnd',
                [
                    'studentId' => $req->user->studentId,
                    'year' => $year,
                    'univId' => $req->user->universityId,
                    'courseId' => $req->params['courseId'],
                    'start' =>  date("Y-m-d H:i:s"),
                    'end' => date("Y-m-d H:i:s")                    
                ]
            )->fetchAll(PDO::FETCH_ASSOC);
        }
        else if ($isTeacher)
        {
            $this->data = $res->getDatabase()->query(
                'SELECT E.id, E.name, E.coeff, E.type, E.date_start as dateStart, E.date_end as dateEnd
                FROM exam E
                    INNER JOIN teacherteaching TT USING (id_course)
                    LEFT JOIN course C ON (C.id = E.id_course)
                    INNER JOIN universityyear Y ON (Y.id = TT.id_year)
                WHERE TT.id_teacher = :teacherId
                    AND Y.year = :year
                    AND C.id_univ = :univId
                    AND C.id = :courseId',
                [
                    'teacherId' => $req->user->teacherId,
                    'year' => $year,
                    'univId' => $req->user->universityId,
                    'courseId' => $req->params['courseId']                  
                ]
            )->fetchAll(PDO::FETCH_ASSOC);
        }
        else
        {
            $this->data = $res->getDatabase()->query(
                'SELECT E.id, E.name, E.coeff, E.type, E.date_start as dateStart, E.date_end as dateEnd
                FROM exam E
                    INNER JOIN universityyear Y ON (Y.id = E.id_year)
                WHERE Y.year = :year
                    AND E.id_course = :courseId
                    AND Y.id_univ = :univId',
                [
                    'year' => $year,
                    'univId' => $req->user->universityId,
                    'courseId' => $req->params['courseId']
                ]
            )->fetchAll(PDO::FETCH_ASSOC);
        }
	}
}