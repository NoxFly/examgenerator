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


class MyExamsController extends ApiController {
    function __construct() {
        $this->guard = new ApiPrivilegesGuard([
            'GET' => UserType::TEACHER | UserType::STUDENT,
        ]);
    }

    /**
     * Affiche la liste des examens :
     * - pour un enseignant, qu'il a créé pour toutes les matières
     *   dont il est le référent, pour l'année en cours
     * - pour un étudiant, qu'il a ou doit passer pour les matières
     *   dont il est participant, pour l'année en cours
     * 
     * Codes de retour possibles :
     * - 200 OK
     * - 401 Utilisateur non authentifié
     * 
     * @param stdClass $req
     * @param ApiSite $res
     */
    public function GET($req, $res) {
        $this->sendData($req, $res, 'getExams');
    }


    /* -------------------------------------------------------- */


	protected function getExams($req, $res) {
        $selection = 'E.id, E.name, E.type, E.date_start as dateStart, E.date_end as dateEnd, C.id as courseId, C.name as courseName, E.is_corrected';

        $filters = [
            'start' => 'E.date_start >=',
            'end' => 'E.date_end <=',
            'type' => 'E.type =',
            'course' => 'C.id ='
        ];
        
        $where = [];

        $queryParams = [
            'univId' => $req->user->universityId,
            'year' => $req->user->year
        ];


        foreach($filters as $filter => $cond) {
            if(isset($req->query[$filter])) {
                array_push($where, $cond . ' :' . $filter);
                $queryParams[$filter] = $req->query[$filter];
            }
        }


        $where = join(' AND ', $where);

        if(mb_strlen($where) > 0) {
            $where = 'AND ' . $where;
        }


        if(bitwiseAND($req->user->privileges, UserType::STUDENT)) { // student
            $queryParams['userId'] = $req->user->studentId;

            $query = "SELECT $selection
                FROM exam E
                    INNER JOIN universityyear Y ON (Y.id = E.id_year)
                    LEFT JOIN examlevel EL ON (EL.id_exam = E.id)
                    LEFT JOIN course C ON (C.id = E.id_course)
                    LEFT JOIN student S ON (S.id_level = EL.id_level)
                WHERE S.id = :userId
                    AND Y.year = :year
                    AND C.id_univ = :univId
                    $where";
        }
        else { // teacher
            $queryParams['userId'] = $req->user->teacherId;

            $query = "SELECT $selection
                FROM exam E
                    INNER JOIN teacherteaching TT USING (id_course)
                    LEFT JOIN course C ON (C.id = E.id_course)
                    INNER JOIN universityyear Y ON (Y.id = TT.id_year)
                WHERE TT.id_teacher = :userId
                    AND Y.year = :year
                    AND C.id_univ = :univId
                    $where";

        }

        $data=$res->getDatabase()->query($query, $queryParams)->fetchAll(PDO::FETCH_ASSOC);

        //ajout champ dans data pour chaque exam "status" enum -> a venir, en cours, fini et corrigé (si réponse a exam ouvert il est fini)
        foreach($data as &$exam)
        {
            //check les dates
            $timeNow = new DateTime("now");
            $now = $timeNow->format('Y-m-d H:i:s');

            $sStart = $exam['dateStart'];
            $sEnd = $exam['dateEnd'];

            //$dStart = (new DateTime($sStart))->format('d/m/Y H:i');
            //$dEnd = (new DateTime($sEnd))->format('d/m/Y H:i');
            
            //$exam['dateStart'] = $dStart;
            //$exam['dateEnd'] = $dEnd;

            // verifier si l'examen est fini, en cours ou a venir
            if ($now < $sStart)
            {
                $state = ExamState::COMING;
            }
            else if ($now < $sEnd)
            {
                $state = ExamState::PENDING;                
            }
            else {
                if ($exam['is_corrected'] == 1)
                {
                    $state = ExamState::REVISED;
                
                }
                else
                {
                    $state = ExamState::DONE; // default state
                }
                
            }

            if (bitwiseAND($req->user->privileges, UserType::STUDENT))
            {
                //si state est fini, on teste si c'est corrigé
                if ($state === ExamState::DONE)
                {
                        $answers = $res->getDatabase()->query(
                            'SELECT A.id_question as questionId, A.points, A.comment, A.answer
                            FROM answer A
                            WHERE A.id_student = :studentId
                                AND A.id_exam = :examId
                            ORDER BY A.id_question',
                            [
                                'studentId' => $req->user->studentId,
                                'examId' => $exam['id']
                            ]
                        )->fetchAll(PDO::FETCH_ASSOC);

                        if (count($answers)!==0)
                        {
                            $state = ExamState::REVISED;
                            foreach ($answers as $answer)
                            {
                                if (is_null($answer['points']))
                                    $state=ExamState::DONE;
                            }
                        }
                }
                //si state est pending, on teste si il y'a déja une réponse
                else if ($state == ExamState::PENDING)
                {
                    $hasAnswered = $res->getDatabase()->query(
                        'SELECT 1
                        FROM answer A
                        WHERE A.id_student = :studentId
                            AND A.id_exam = :examId
                        LIMIT 1',
                        [
                            'studentId' => $req->user->studentId,
                            'examId' => $exam['id']
                        ]
                    )->fetchColumn();

                    if ($hasAnswered)
                    {
                        $state = ExamState::DONE;
                    }
                }
            }

            unset($exam['is_corrected']);
            $exam['status'] = $state;
        }    
        
        $this->data = $data;
	}
}