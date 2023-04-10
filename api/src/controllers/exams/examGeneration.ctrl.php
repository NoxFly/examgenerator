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


class ExamGenerationController extends ApiController {
	function __construct() {
        $this->guard = new ApiPrivilegesGuard([
            'GET' => UserType::TEACHER,
			'PUT' => UserType::TEACHER
        ]);
    }

	/**
     * Génère 3 sujets aléatoires d'examen, en choisissant des
	 * des questions de façon aléatoire dans les chapitres donnés
	 * issues d'une matière dont l'enseignant entamment la
	 * démarche est le référent pour l'année en cours.
	 * Un nombre de questions maximum est à donner.
	 * L'équilibre des questions par chapitre est également à fournir.
	 * >> Pondération ?
	 * Il est possible de ne choisir que des questions de type QCM.
	 * Au contraire, il est également possible de ne choisir que des
	 * questions de rédaction.
	 * Ces paramètres sont représentés sous forme de pourcentage.
	 * La somme de ces pourcentages doivent valoir 100%.
	 * 
	 * Codes de retour possibles :
     * - 200 OK
	 * - 401 Utilisateur non authentifié
	 * - 403 N'a pas accès à cette ressource
	 * 
     * @param stdClass $req
     * @param ApiSite $res
     */
    public function GET($req, $res) {
        $this->sendData($req, $res, 'generateExamSubjects');
    }

	/**
     * Permet à l'enseignant référent de
	 * - créer un nouvel examen dans sa matière (overview + barème).
	 * - rajouter ou créer pour la première fois (et non modifier) la liste
	 *   des niveaux participant à cet examen.
	 * Code 403 si pas référent.
	 * Code 404 si examen / matière introuvable pour l'ajout des niveaux.
	 * 
	 * Codes de retour possibles :
     * - 200 OK
	 * - 400 Champs manquants pour l'ajout de niveaux
	 * - 401 Utilisateur non authentifié
	 * - 403 N'a pas l'autorisation nécessaire pour créer un examen dans cette matière
	 * - 404 La matière n'existe pas / l'examen n'existe pas si ajout de niveaux
	 * - 500 Erreur pendant l'exécution de la requête dans la base de données
	 * 
     * @param stdClass $req
     * @param ApiSite $res
     */
	public function PUT($req, $res) {
		$this->sendData($req, $res, 'createExam');
	}


    /* -------------------------------------------------------- */

    protected function generateExamSubjects($req, $res) {
		$courseId = $req->query['courseId']?? NULL;
		$chapters = $req->query['chapters']?? NULL;
		$satQuestionCount = $req->query['qCount']?? NULL;
		$mcqPerc = $req->query['mcqPerc']?? NULL;

		if($courseId === NULL || $chapters === NULL || $satQuestionCount === NULL || $mcqPerc === NULL) {
			throw new ApiException(400, 'Missing fields');
		}

		if(!is_numeric($mcqPerc)) {
			throw new ApiException(400, 'Wrong format given (mcqPercentage)');
		}

		if(!is_numeric($satQuestionCount)) {
			throw new ApiException(400, 'Wrong format given (questionCount)');
		}

		if(!preg_match('/^\d+[,\d+]*$/', $chapters)) {
			throw new ApiException(400, 'Wrong format given (chapters)');
		}

		$achapters = explode(',', $chapters);
		$chapters = str_replace(',', ', ', $chapters);

		$mcqPerc = intval($mcqPerc);
		$satQuestionCount = intval($satQuestionCount);

		if($mcqPerc < 0 || $mcqPerc > 100) {
			throw new ApiException(400, 'Out of range (mcqPercentage)');
		}

		$db = $res->getDatabase();


		// strategy to cast courseId from string to int
		// at the same time it does a request to verify it exists
		// and the user that requested is the referent.
		$courseId = $db->query(
			'SELECT TT.id_course
			FROM teacherteaching TT
			LEFT JOIN universityyear Y ON (Y.id = TT.id_year)
			WHERE TT.id_teacher = :teacherId
				AND TT.id_course = :courseId
				AND Y.year = :year',
			[
				'teacherId' => $req->user->teacherId,
				'courseId' => $courseId,
				'year' => $req->user->year
			]
		)->fetchColumn();

		if(!$courseId) {
			throw new ApiException(403);
		}

		// chapters format : pos1, pos2, pos3, ...
		$chaptersData = $db->query(
			"SELECT id, label as label
			FROM coursechapter
			WHERE id_course = :courseId
				AND position IN ($chapters)
			ORDER BY position",
			[
				'courseId' => $courseId
			]
		)->fetchAll(PDO::FETCH_OBJ);

		if(count($chaptersData) < count($achapters)) {
			throw new ApiException(400, 'Unknown chapter id');
		}

		try {
			$subjects = ExamenService::generateSubject($db, 3, $courseId, $chapters, $satQuestionCount, $mcqPerc);

			$this->data = [
				'chapters' => $chaptersData,
				'subjects' => $subjects
			];
		}
		catch(ApiException $e) {
			$db->rollback();
			throw $e;
		}
		catch(Exception $e) {
			throw new ApiException(500, $e->getMessage());
		}
    }

	protected function createExam($req, $res) {
		$db = $res->getDatabase();

		$yearId = $this->getYearId($req, $res);

		$examId = $req->body['examId']?? NULL;
		$r = NULL;

		if($examId) {
			$r = $db->query(
				'SELECT TT.id_teacher as teacherId, E.type as examType
				FROM exam E
					INNER JOIN course C ON (C.id = E.id_course)
					LEFT JOIN teacherteaching TT ON (TT.id_course = C.id)
				WHERE C.id_univ = :univId
					AND TT.id_year = :yearId
					AND E.id = :examId',
				[
					'univId' => $req->user->universityId,
					'yearId' => $yearId,
					'examId' => $examId
				]
			)->fetch();
	
			if(!$r) {
				throw new ApiException(404);
			}

			if($r['teacherId'] !== $req->user->teacherId) {
				throw new ApiException(403);
			}
		}

		$examType = $req->body['overview']['type']?? $r['examType']?? NULL;

		switch($examType) {
			case ExamType::CC:
			case ExamType::CI:
			case ExamType::CF:
				break;
			default:
				throw new ApiException(400, 'Unknown exam type');
		}

		if(isset($req->body['overview'])) {
			$fields = [
				'courseId' => NULL,
				'name' => NULL,
				'coeff' => NULL,
				'type' => NULL,
				'dateStart' => NULL,
				'dateEnd' => NULL
			];

			foreach($fields as $f => $v) {
				if(!isset($req->body['overview'][$f])) {
					throw new ApiException(400, 'Missing field ' . $f);
				}

				$bv = $req->body['overview'][$f];

				if(substr($f, 0, 4) === 'date') {
					$fields[$f] = date("Y-m-d H:i:s", $bv);
				}
				else {
					$fields[$f] = $bv;
				}
			}

			$fields['yearId'] = $yearId;

			$teacherId = $db->query(
				'SELECT TT.id_teacher as teacherId
				FROM course C
					LEFT JOIN teacherteaching TT ON (TT.id_course = C.id)
				WHERE C.id_univ = :univId
					AND TT.id_year = :yearId
					AND C.id = :courseId',
				[
					'univId' => $req->user->universityId,
					'yearId' => $yearId,
					'courseId' => $fields['courseId']
				]
			)->fetchColumn();
	
			if(!$teacherId) {
				throw new ApiException(404);
			}
	
			if($teacherId !== $req->user->teacherId) {
				throw new ApiException(403);
			}

			$db->query(
				'INSERT INTO exam (id_course, name, coeff, type, id_year, date_start, date_end)
					VALUES (:courseId, :name, :coeff, :type, :yearId, :dateStart, :dateEnd)',
				$fields
			)->fetchAll(PDO::FETCH_ASSOC);

			$examId = $db->getLastInsertedId();

			$this->data['examId'] = $examId;
		}

		if((isset($req->body['questions']) || isset($req->body['target'])) && !$examId) {
			throw new ApiException(400);
		}

		if(isset($req->body['questions'])) {
			$this->setExamContent($db, $examId, $req->body['questions']);
		}
		
		if(isset($req->body['target'])) {
			$this->makeLevelExamAssociation($db, $examId, $req->body['target']);
		}
	}

	/**
	 * Duplicate function of examDetails
	 */
	protected function makeLevelExamAssociation($db, $examId, $levels) {
		$values = '';
		$params = [];

		$l = count($levels) - 1;

		foreach($levels as $i => $target) {
			$values .= "(:levelId$i, :examId$i)";

			if($i < $l) {
				$values .= ', ';
			}

			$params['levelId'.$i] = $target;
			$params['examId'.$i] = $examId;
		}

		try {
			$db->beginTransaction();
			$db->query(
				"INSERT INTO examlevel (id_level, id_exam) VALUES $values",
				$params
			);
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

	/**
	 * @param Database $db
	 * @param int $examId
	 * @param int[][] $questions
	 */
	protected function setExamContent($db, $examId, $questions) {
		try {
			$values = [];
			$params = [];

			$i = 0;

			foreach($questions as $qId => $scale) {
				array_push($values, "(:examId$i, :questionId$i, :scale$i, :negScale$i)");
				$params["examId$i"] = $examId;
				$params["questionId$i"] = $qId;
				$params["scale$i"] = $scale[0]?? NULL;
				$params["negScale$i"] = $scale[1]?? NULL;
				$i++;
			}

			$values = join(',', $values);

			$db->beginTransaction();
			$db->query(
				"INSERT INTO examquestion (id_exam, id_question, nb_points, neg_points) VALUES $values",
				$params
			);
			

			$ids = $db->query('SELECT DISTINCT type FROM question WHERE id IN ('.join(', ', array_keys($questions)).')')->fetchAll(PDO::FETCH_ASSOC);

			$ids = array_filter($ids, function($id) {return $id["type"] == QuestionType::TEXT;});

			if (empty($ids))
			{
				$db->query("UPDATE exam SET is_corrected= 1 WHERE id = $examId");
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