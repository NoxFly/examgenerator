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
require_once(PATH_SERVICES . 'Examen.php');


class ExamResultsController extends ApiController {
    function __construct() {
        $this->guard = new ApiPrivilegesGuard([
            'GET' => UserType::ADMIN | UserType::TEACHER | UserType::STUDENT,
			'PUT' => UserType::STUDENT
        ]);
    }

    /**
     * - Pour l'enseignant référent :
     *   Affiche les résultats de l'examen :
     *   - les statistiques globales (moyenne, écart-type, ...)
     *   - les résultats individuels de chaque participant dans une liste.
     * - pour un enseignant non référent de la matière : code 404
     * - Pour un étudiant :
     *   Affiche ses résultats pour l'examen donné (note[s], commentaires, ...)
     * 
     * Codes de retour possibles :
     * - 200 OK
     * - 401 Utilisateur non authentifié
     * - 404 L'examen ou sa matière n'existe pas
     * - 403 L'utilisateur n'a pas l'accès à cette ressource (car n'appartient pas à la matière ou car examen pas encore fini)
     * 
     * @param stdClass $req
     * @param ApiSite $res
     */
    public function GET($req, $res) {
        $this->sendData($req, $res, 'getExamResults');
    }

    /**
     * Accessible uniquement par un étudiant qui n'a pas encore répondu
     * et souhaite répondre à un examen.
     * 
     * Codes de retour possibles :
     * - 200 OK
     * - 400 Les champs de réponse ne respectent pas la forme demandée
     * - 401 Utilisateur non authentifié
     * - 403 Ne peut pas prétendre répondre à cet examen
	 * - 500 Erreur pendant l'exécution de la requête dans la base de données
     * 
     * @param stdClass $req
     * @param ApiSite $res
     */
	public function PUT($req, $res) {
		$this->sendData($req, $res, 'answerExam');
	}


    /* -------------------------------------------------------- */


    protected function getExamResults($req, $res) {
        $db = $res->getDatabase();

        $examId = $req->params['examId'];

        $isStudent = bitwiseAND($req->user->privileges, UserType::STUDENT);
        $isTeacher = bitwiseAND($req->user->privileges, UserType::TEACHER);

        $this->data = (object)array();

        $fromClause = '';
        $whereClause = '';

        $yearId = $this->getYearId($req, $res);

        $params = [
            'univId' => $req->user->universityId,
            'examId' => $examId,
        ];


        if ($isStudent)
        {
            $fromClause = "INNER JOIN courselevelyear CLY ON (CLY.id_course = E.id_course)
                INNER JOIN student S ON (S.id_level = CLY.id_level)";
            $whereClause = "S.id = :studentId AND S.id_year = E.id_year";

            $params['studentId'] = $req->user->studentId;
        }
        else if ($isTeacher)
        {
            $fromClause = "INNER JOIN teacherteaching TT ON (TT.id_course = E.id_course)";
            $whereClause = "TT.id_teacher = :teacherId AND TT.id_year = E.id_year";

            $params['teacherId'] = $req->user->teacherId;
            $params['teacherId'] = $req->user->teacherId;
        }
        else // admin
        {
            $fromClause = "INNER JOIN course C ON (C.id = E.id_course)";
            $params['univId'] = $req->user->universityId;
        }

        $overview = $db->query(
			// overview
			"SELECT E.name, E.coeff, E.type, Y.year,
				E.date_start as dateStart, E.date_end as dateEnd,
				UNIX_TIMESTAMP(E.date_start) as startTime, UNIX_TIMESTAMP(E.date_end) as endTime,
				C.name as courseName
			FROM exam E
				JOIN course C ON (C.id = E.id_course)
				JOIN universityyear Y ON (Y.id = E.id_year)
                $fromClause
			WHERE C.id_univ = :univId
				AND E.id = :examId
                AND $whereClause",
			$params
		)->fetchObject();

            
        if (!$overview->endTime)
        {
            throw new ApiException(404);
        }

        if (date("Y-m-d H:i:s") <= $overview->dateEnd)
        {
            throw new ApiException(403, 'Exam is not finished yet');
        }

        $this->data->overview = $overview;

    
        $chapters = $db->query(
            'SELECT DISTINCT(CC.id) as id, CC.label
            FROM coursechapter CC
                INNER JOIN question Q ON (Q.id_chapter = CC.id)
                INNER JOIN examquestion EQ ON (EQ.id_question = Q.id)
            WHERE EQ.id_exam = :examId
            ORDER BY CC.position',
            [
                'examId' => $examId
            ]
        )->fetchAll(PDO::FETCH_ASSOC);

        $this->data->chapters = $chapters;

        $this->data->questions = ExamenService::getQuestionsFromExam($db, $req->user, $examId, true);

        $questionsType = [];
        
        foreach($this->data->questions as $q) {
            $questionsType[$q->id] = $q->type;
        }

        if ($isStudent)
        {
            $answers = $db->query(
                'SELECT A.id_question as questionId, A.points, A.comment, A.answer
                FROM answer A
                WHERE A.id_student = :studentId
                    AND A.id_exam = :examId
                ORDER BY A.id_question',
                [
                    'studentId' => $req->user->studentId,
                    'examId' => $examId
                ]
            )->fetchAll(PDO::FETCH_ASSOC);
            
            // tester si la copie est corrigée
            foreach($answers as &$row)
            {
                if (is_null($row['points']))
                {
                    throw new ApiException(403, 'Exam is not corrected yet');
                }

                switch($questionsType[$row['questionId']]) {
                    case QuestionType::UNIQUE:
                        $row['answer'] = intval($row['answer']);
                        break;
                    case QuestionType::MULTIPLE:
                        $q = explode(';', $row['answer']);
                        $row['answer'] = array_map(function($e) { return intval($e); }, $q);
                        break;
                }
            }

            $this->data->answers = $answers;
        }
        else
        {
            $rowsAnswers = $db->query(
                'SELECT U.uuid, S.id as studentId, A.id_question as questionId, A.points, A.comment, A.answer
                FROM answer A
                    INNER JOIN student S ON (S.id = A.id_student)
                    INNER JOIN user U ON (U.id = S.id_user)
                WHERE A.id_exam = :examId
                ORDER BY U.uuid, A.id_question',
                [
                    'examId' => $examId
                ]
            )->fetchAll(PDO::FETCH_ASSOC);

            $answers = (object)array();

            foreach($rowsAnswers as $answer) {
                $uuid = $answer['uuid'];

                if(!isset($answers->{$uuid})) {
                    $answers->{$uuid} = (object)array(
                        'uuid' => $uuid,
                        'studentId' => $answer['studentId'],
                        'finalMark' => 0,
                        'answers' => []
                    );
                }
                
                unset($answer['uuid'], $answer['studentId']);

                switch($questionsType[$answer['questionId']]) {
                    case QuestionType::UNIQUE:
                        $answer['answer'] = intval($answer['answer']);
                        break;
                    case QuestionType::MULTIPLE:
                        $q = explode(';', $answer['answer']);
                        $answer['answer'] = array_map(function($e) { return intval($e); }, $q);
                        break;
                }

                array_push($answers->{$uuid}->answers, $answer);

                if ($answers->{$uuid}->finalMark === NULL)
                {
                    continue;
                }
                else if ($answer['points'] === NULL)
                {
                    $answers->{$uuid}->finalMark = NULL;
                    continue;
                }

                $answers->{$uuid}->finalMark += $answer['points'];
            }

            $this->data->answers = $answers;
        }
    }


    protected function answerExam($req, $res) {
        $year = $req->user->year;
        $isStudent = bitwiseAND($req->user->privileges, UserType::STUDENT);

        $examId = $req->params['examId'];

        $db = $res->getDatabase();

        if ($isStudent)
        {
            $dates = $db->query(
                'SELECT E.date_start as dateStart, E.date_end as dateEnd
                FROM exam E
                    INNER JOIN courselevelyear CLY ON (CLY.id_course = E.id_course)
                    INNER JOIN universityyear Y ON (Y.id = CLY.id_year)
                    INNER JOIN student S ON (S.id_level = CLY.id_level)
                WHERE S.id = :studentId
                    AND E.id = :examId
                    AND Y.year = :year
                    AND S.id_year = E.id_year',
                [
                    'studentId' => $req->user->studentId,
                    'examId'    => $examId,
                    'year'      => $year
                ]
            )->fetchObject();
        }

        if (!$dates)
        {
            throw new ApiException(404);
        }

        $now = date("Y-m-d H:i:s");

        if ($now <= $dates->dateStart || $dates->dateEnd <= $now)
        {
            throw new ApiException(403, 'Cannot answer when out of dates');
        }
        
        $hasAnswered = $db->query(
            'SELECT 1
            FROM answer A
                LEFT JOIN exam E ON (E.id = A.id_exam)
                LEFT JOIN courselevelyear CLY ON (CLY.id_course = E.id_course)
                LEFT JOIN student S ON (S.id_level = CLY.id_level)
            WHERE S.id = :studentId
                AND E.id = :examId
            LIMIT 1',
            [
                'studentId' => $req->user->studentId,
                'examId'    => $examId
            ]
        )->fetch();
        
        if($hasAnswered) {
            throw new ApiException(403, 'Already answered to this exam');
        }

        $dbExQuestions = ExamenService::getQuestionsFromExam($db, $req->user, $examId, true);

        $exQuestions = array();

        foreach($dbExQuestions as $q) {
            $exQuestions[$q->id] = $q;
        }

        try {
            $db->beginTransaction();

            foreach($req->body as $questionId => $answer) {
                $question = $exQuestions[$questionId] ?? NULL;

                if(!$question) {
                    throw new ApiException(400, 'Wrong question id');
                }

                $points = ExamenService::markQuestion($question, $answer);

                if(gettype($answer) === 'array') {
                    $answer = join(';', $answer);
                }

                $db->query(
                    'INSERT INTO answer (id_student, id_question, id_exam, points, answer)
                        VALUES (:studentId, :questionId, :examId, :points, :answer)',
                    [
                        'studentId' => $req->user->studentId,
                        'questionId'=> $questionId,
                        'examId'    => $req->params['examId'],
                        'points'    => $points,
                        'answer'    => $answer
                    ]
                );
            }

            $db->commit();
        }
        catch(ApiException $e) {
            $db->rollback();
            throw $e;
        }
        catch(Exception $e) {
            $db->rollback();
            throw new ApiException(500, $e->getMessage());
        }
    }
}