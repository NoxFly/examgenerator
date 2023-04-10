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


class CourseListController extends ApiController {
    function __construct() {
        $this->guard = new ApiPrivilegesGuard([
            'GET' => UserType::ADMIN | UserType::TEACHER | UserType::STUDENT
        ]);
    }

    /**
     * Affiche la liste des matières :
     * - pour un enseignant, où il est référent
     * - pour un étudiant, qu'il doit suivre à travers son cursus
     * 
     * Il est possible d'être enseignant et étudiant en même temps (doctorant),
     * L'objet est donc du type { asReferent: course[], asParticipant: course[] }.
     * 
     * Si un identifiant de matière est passé en paramètre, n'affiche que l'objet
     * de cette matière, avec la liste des niveaux qui ont accès à cette matière
     * pour l'année cible (par défaut l'année courante).
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
        $db = $res->getDatabase();

        $singleCourse = isset($req->params['courseId']);

        $query = [
            'where' => '',
            'params' => []
        ];

        if($singleCourse) {
            $query['where'] = 'AND C.id = :courseId';
            $query['params']['courseId'] = $req->params['courseId'];
        }

        // admin
        if(bitwiseAND($req->user->privileges, UserType::ADMIN)) {
            $query['params']['univId'] = $req->user->universityId;
            $this->adminCourses($req, $res, $query, $singleCourse);
        }
        // teacher | student
        else {
            $query['params']['year'] = $req->query['year']?? $req->user->year;

            $dft = [];

            if($singleCourse) {
                $dft = false;
            }

            $this->data = (object)array(
                'asReferent' => $dft,
                'asParticipant' => $dft
            );

            if($req->user->teacherId) {
                $query['params']['teacherId'] = $req->user->teacherId;
                $this->userCourses($db, $query, $singleCourse, 'teacher', 'asReferent');
                unset($query['params']['teacherId']);
            }

            if($req->user->studentId) {
                $levelId = $db
                    ->query($this->studentLevelQuery, ['studentId' => $req->user->studentId])
                    ->fetch()['id']?? -1;

                $query['params']['levelId'] = $levelId;

                $this->userCourses($db, $query, $singleCourse, 'student', 'asParticipant');

                unset($query['params']['levelId']);
            }

            if($singleCourse) {
                $rC = $this->data->asReferent === $dft;
                $pC = $this->data->asParticipant === $dft;

                if($rC && $pC) {
                    throw new ApiException(404);
                }

                $k = $pC? 'asReferent' : 'asParticipant';
                $this->data = $this->data->{$k};

                // list levels that have access to this course
                $levels = $db->query(
                    'SELECT DISTINCT L.id, L.name, UC.name as cursusName
                    FROM level L
                        INNER JOIN universitycourse UC ON (UC.id = L.id_universitycourse)
                        INNER JOIN university U ON (U.id = UC.id_univ)
                        INNER JOIN courselevelyear CLY ON (CLY.id_level = L.id)
                        INNER JOIN universityyear Y ON (Y.id_univ = U.id)
                    WHERE U.id = :univId
                        AND CLY.id_course = :courseId
                        AND Y.year = :year',
                    [
                        'univId' => $req->user->universityId,
                        'courseId' => $this->data->id,
                        'year' => $query['params']['year']
                    ]
                )->fetchAll(PDO::FETCH_ASSOC);

                $this->data->levels = $levels;
            }
        }
	}
    

    protected function adminCourses($req, $res, $query, $singleCourse) {
        $yearId = $this->getYearId($req, $res);

        $query['params']['yearId'] = $yearId;

        $r = "WITH courses AS (
            SELECT C.id, C.name
            FROM course C
            WHERE C.id_univ = :univId "
            . $query['where']
            . "), "
            . $this->adminQuery;

        $this->data = $res->getDatabase()->query($r, $query['params']);

        if($singleCourse) {
            $this->data = $this->formatCourse($this->data->fetchObject());
        }
        else {
            $this->data = $this->data->fetchAll(PDO::FETCH_OBJ);

            foreach($this->data as $k => $course) {
                $this->data[$k] = $this->formatCourse($course);
            }
        }
    }

    protected function userCourses($db, $query, $singleCourse, $userType, $field) {
        $userQuery = $this->{$userType . 'Query'};
        $req = $userQuery . " " . $query['where'];

        $data = $db->query($req, $query['params']);

        $this->data->{$field} = $singleCourse
            ? $data->fetchObject()
            : $data->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function formatCourse($course) {
        if($course->years === NULL) {
            $course->years = [];
        }
        else {
            $course->years = array_map(function($y) {
                return intval($y);
            }, explode(',', $course->years));
        }

        if($course->cursus === NULL) {
            $course->cursus = [];
        }
        else {
            $course->cursus = array_map(function($c) {
                return intval($c);
            }, explode(',', $course->cursus));
        }

        if($course->referentId !== NULL) {
            $course->referent = [
                'teacherId' => $course->referentId,
                'userId' => $course->referentUserId,
                'uuid' => $course->referentUserUUID,
                'firstname' => $course->referentFirstname,
                'lastname' => $course->referentLastname
            ];
        }
        else {
            $course->referent = NULL;
        }

        unset($course->referentId);
        unset($course->referentUserId);
        unset($course->referentFirstname);
        unset($course->referentLastname);
        unset($course->referentUserUUID);

        return $course;
    }





    private $adminQuery = "ue AS (
        SELECT C.id, C.name,
            GROUP_CONCAT(DISTINCT Y.year ORDER BY Y.year DESC) AS years,
            GROUP_CONCAT(DISTINCT L.id_universitycourse) AS cursus
        FROM courses C
            LEFT JOIN courselevelyear CLY ON (CLY.id_course = C.id)
            LEFT JOIN level L ON (L.id = CLY.id_level)
            LEFT JOIN universityyear Y ON (Y.id = CLY.id_year)
        GROUP BY C.id
    ),
    tty AS (
        SELECT TT.id_teacher as referentId, TT.id_course as id,
            U.firstname, U.lastname, U.id as userId, U.uuid as userUUID
        FROM courses UE
            INNER JOIN teacherteaching TT ON (TT.id_course = UE.id)
            INNER JOIN teacher T ON (T.id = TT.id_teacher)
            INNER JOIN user U ON (U.id = T.id_user)
        WHERE TT.id_year = :yearId
    )
    SELECT ue.id, ue.name, ue.years,
        tty.referentId, tty.userId as referentUserId, tty.userUUID as referentUserUUID,
        tty.firstname as referentFirstname, tty.lastname as referentLastname,
        ue.cursus
    FROM ue
        LEFT JOIN tty USING (id)";

    private $teacherQuery = "SELECT C.id, C.name
        FROM course C
            INNER JOIN teacherteaching TT ON (TT.id_course = C.id)
            INNER JOIN universityyear Y ON (Y.id = TT.id_year)
        WHERE TT.id_teacher = :teacherId
            AND Y.year = :year";

    private $studentQuery = "SELECT C.id, C.name
        FROM course C
            INNER JOIN courselevelyear CLY ON (CLY.id_course = C.id)
            INNER JOIN universityyear Y ON (Y.id = CLY.id_year)
        WHERE CLY.id_level = :levelId
            AND Y.year = :year";

    private $studentLevelQuery = 'SELECT L.id
        FROM level L
            LEFT JOIN student S ON (S.id_level = L.id)
        WHERE S.id = :studentId';
}