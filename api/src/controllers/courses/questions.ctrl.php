<?php

defined('_NOX') or die('401 Unauthorized');


require_once(PATH_TEMPLATES . 'ApiController.php');
require_once(PATH_GUARDS . 'ApiPrivileges.guard.php');
require_once(PATH_SERVICES . 'Examen.php');


class QuestionsController extends ApiController {
    function __construct() {
        $this->guard = new ApiPrivilegesGuard(
            UserType::TEACHER
        );
    }

    /**
     * Affiche la liste des questions pour un chapitre :
     * - id, énoncé, propositions de réponses, type, et réponse si c'est l'enseignant référent.
     * Si la matière ou le chapitre n'existent pas, code 404.
     * 
     * Codes de retour possibles :
     * - 200 OK
     * - 401 Utilisateur non authentifié
	 * - 404 La matière ou le chapitre n'existent pas
     * 
     * @param stdClass $req
     * @param ApiSite $res
     */
    public function GET($req, $res) {
        $this->sendData($req, $res, 'getQuestion');
    }

    /**
     * Permet à l'enseignant référent de modifier une question en particulier.
     * Si la matière, le chapitre, ou la question n'existent pas, code 404.
     * Si l'utilisateur n'est pas l'enseignant référent, code 403.
     * 
     * Note : cet endpoint ne permet de modifier le barème d'une question.
     * Pour ça, voir les endpoints sur les examens. Un barème est fixé en fonction
     * de l'ensemble des questions d'un examen, et non de la question en elle-même.
     * 
     * Codes de retour possibles :
     * - 200 OK
     * - 401 Utilisateur non authentifié
     * - 403 N'a pas l'autorisation nécessaire pour modifier la question
     * - 404 La matière, le chapitre, ou la question n'existent pas
	 * - 500 Erreur pendant l'exécution de la requête dans la base de données
     * 
     * @param stdClass $req
     * @param ApiSite $res
     */
	public function POST($req, $res) {
        $this->sendData($req, $res, 'modifyQuestion');
    }

    /**
     * Permet à l'enseignant référent de créer une nouvelle question, associée
     * à un chapitre.
     * Si la matière ou le chapitre n'existent pas, code 404.
     * 
     * Codes de retour possibles :
     * - 200 OK
     * - 401 Utilisateur non authentifié
     * - 400 Les champs données ne respectent pas la forme demandée
     * - 403 N'a pas l'autorisation nécessaire pour créer une question dans cette matière
     * - 404 La matière ou le chapitre n'existent pas
	 * - 500 Erreur pendant l'exécution de la requête dans la base de données
     * 
     * @param stdClass $req
     * @param ApiSite $res
     */
	public function PUT($req, $res) {
        $this->sendData($req, $res, 'createQuestion');
    }

    /**
     * Permet à l'enseignant référent de supprimer une question.
     * 
     * Attention, il peut supprimer toutes les questions de la matière
     * dont il est référent, même celles des années où il ne l'était pas.
     * 
     * Attention bis, cela impacte tout ce qui est lié à la question :
     * supprime tout ce qui est lié et dépendant (examen).
     * 
     * Codes de retour possibles :
     * - 200 OK
     * - 401 Utilisateur non authentifié
     * - 403 N'a pas l'autorisation nécessaire pour supprimer la question
     * - 404 La matière, le chapitre, ou la question n'existent pas
	 * - 500 Erreur pendant l'exécution de la requête dans la base de données
     * 
     * @param stdClass $req
     * @param ApiSite $res
     */
	public function DELETE($req, $res) {
        $this->sendData($req, $res, 'deleteQuestion');
    }


    /* -------------------------------------------------------- */


    protected function getQuestion($req, $res) {
        $db = $res->getDatabase();

        $r = $db->query(
            'SELECT C.id
            FROM course C
                INNER JOIN coursechapter CC ON (CC.id_course = C.id)
            WHERE C.id_univ = :courseId
                AND CC.id = :chapterId',
            [
                'courseId' => $req->params['courseId'],
                'chapterId' => $req->params['chapterId']
            ]
        )->fetch();

        if(!$r) {
            throw new ApiException(404);
        }

        $this->data = ExamenService::getQuestionsFromChapter($db, $req->user, $req->params['courseId'], $req->params['chapterId']);
    }

    protected function modifyQuestion($req, $res) {
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
        

        $fields = [
			// param => sql field
			'state' => 'state',
			'proposals' => 'proposals',
			'answers' => 'answers'
        ];

        $values = '';
		$queryParams = [];

		$queryParams['questionId'] = intval($req->params['questionId']);

        foreach($fields as $f => $c) {
			if(isset($req->body[$f])) {
                $queryParams[$f] = $req->body[$f];
				$values .= "$c = :$f,";
			}
		}


        if(isset($req->body['type'])) {
            $type = $req->body['type'];

            switch($type) {
                case QuestionType::TEXT:
                case QuestionType::UNIQUE:
                case QuestionType::MULTIPLE:
                    $queryParams['type'] = $type;
                    $values .= "type = :type";
                    break;
                default:
                    throw new ApiException(400, 'Unknown question type');
            }
        }


		$len = mb_strlen($values);

        if($len > 0) {
            if($values[$len-1] === ',') {
                $values = rtrim($values, ',');
            }

            $res->getDatabase()->query(
                "UPDATE question
                SET $values
                WHERE id = :questionId",
                $queryParams
            );
        }
    }

    protected function createQuestion($req, $res) {
        $courseId   = $req->params['courseId'];
        $chapterId  = $req->params['chapterId'];
        $univId     = $req->user->universityId;

        $state      = $req->body['state']?? NULL;
        $proposals  = $req->body['proposals']?? NULL;
        $answers    = $req->body['answers']?? NULL;
        $type       = $req->body['type']?? NULL;

        if($state === NULL || $proposals === NULL || $answers === NULL || $type === NULL) {
            throw new ApiException(400, 'Missing fields');
        }

        switch($type) {
            case QuestionType::TEXT:
            case QuestionType::UNIQUE:
            case QuestionType::MULTIPLE:
                break;
            default:
                throw new ApiException(400, 'Unknown question type');
        }

        $state = trim($state);
        $proposals = trim($proposals);
        $answers = trim($answers);

        if(mb_strlen($state) === 0 || mb_strlen($answers) === 0
            || (
                $type !== QuestionType::TEXT && mb_strlen($proposals) === 0
            )
        ) {
            throw new ApiException(400);
        }

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
        )->fetchColumn();

        if(!$r) {
            throw new ApiException(404);
        }

        if($r !== $req->user->teacherId) {
            throw new ApiException(403);
        }

        $res->getDatabase()->query(
            'INSERT INTO question (id_chapter, state, proposals, answers, type)
                VALUES (:chapterId, :state, :proposals, :answers, :type)',
            [
                'chapterId' => $chapterId,
                'state' => $state,
                'proposals' => $proposals,
                'answers' => $answers,
                'type' => $type
            ]
        );

		$this->data['questionId'] = $res->getDatabase()->getLastInsertedId();
    }

    protected function deleteQuestion($req, $res) {
        $r = $res->getDatabase()->query(
            'SELECT TT.id_teacher as teacherId
            FROM question Q
                INNER JOIN coursechapter CC ON (CC.id = Q.id_chapter)
                INNER JOIN course C ON (C.id = CC.id_course)
                LEFT JOIN teacherteaching TT ON (TT.id_course = C.id)
                LEFT JOIN universityyear Y ON (Y.id = TT.id_year)
            WHERE C.id_univ = :univId
                AND C.id = :courseId
                AND CC.id = :chapterId
                AND Q.id = :questionId
                AND Y.year = :year',
            [
                'univId' => $req->user->universityId,
                'chapterId' => $req->params['chapterId'],
                'courseId' => $req->params['courseId'],
                'questionId' => $req->params['questionId'],
                'year' => $req->user->year
            ]
        )->fetch();

        if(!$r) {
            throw new ApiException(404);
        }

        if($r['teacherId'] !== $req->user->teacherId) {
            throw new ApiException(403);
        }

        $res->getDatabase()->query(
            'DELETE FROM question
            WHERE id = :questionId',
            [
                'questionId' => $req->params['questionId']
            ]
        );
    }
}