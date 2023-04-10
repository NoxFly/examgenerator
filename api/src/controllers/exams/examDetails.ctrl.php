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


class ExamDetailsController extends ApiController {
	function __construct() {
        $this->guard = new ApiPrivilegesGuard([
            'GET' 		=> UserType::TEACHER | UserType::STUDENT,
			'DELETE' 	=> UserType::TEACHER
        ]);
    }

	/**
     * Affiche les détails de l'examen en question.
	 * Pour l'enseignant référent, affiche également les réponses pour chaque question.
	 * Pour un étudiant, cache les réponses.
	 * Pour un enseignant non référent à cette matière, code 404.
	 * A part pour l'enseignant référent, n'est pas accessible en dehors des dates de
	 * début et de fin de l'examen. Un étudiant souhaitant avoir les détails de
	 * cet examen doit passer par ses résultats. Code 403 sinon.
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
        $this->sendData($req, $res, 'getExam');
    }

	/**
     * Modifie l'examen. Accessible uniquement par l'enseignant référent.
	 * Permet de modifier :
	 * - L'overview (nom, coeff, type, dates)
	 * - Le barème
	 * - Les niveaux participants à cet examen
	 * Code 403 si pas référent.
	 * Code 404 si matière ou examen pas trouvé.
	 * 
	 * Codes de retour possibles :
     * - 200 OK
	 * - 401 Utilisateur non authentifié
	 * - 403 N'est pas autorisé à modiier cette ressource
	 * - 404 L'examen n'existe pas
	 * - 500 Erreur pendant l'exécution de la requête dans la base de données.
	 * 
     * @param stdClass $req
     * @param ApiSite $res
     */
	public function POST($req, $res) {
		$this->sendData($req, $res, 'modifyExam');
	}

	/**
     * Permet à l'enseignant référent de supprimer un examen dans sa matière
	 * si et seulement si celui-ci n'a pas encore commencé.
	 * Code 403 si pas référent ou déjà commencé / fini.
	 * Code 404 si introuvable.
	 * 
	 * Codes de retour possibles :
     * - 200 OK
	 * - 401 Utilisateur non authentifié
	 * - 403 N'a pas l'autorisation nécessaire pour supprimer cet examen, ou ne peut pas le supprimer après son commencement
	 * - 404 L'examen ou sa matière n'existe pas
	 * - 500 Erreur pendant l'exécution de la requête dans la base de données.
	 * 
     * @param stdClass $req
     * @param ApiSite $res
     */
	public function DELETE($req, $res) {
		$this->sendData($req, $res, 'deleteExam');
	}


    /* -------------------------------------------------------- */


	protected function getExam($req, $res) {
        $yearId = $this->getYearId($req, $res);
		$examId = $req->params['examId'];

		$isStudent = bitwiseAND($req->user->privileges, UserType::STUDENT);
		$now = date("Y-m-d H:i:s");

		$db = $res->getDatabase();

		$overview = $db->query(
			// overview
			'SELECT E.name, E.coeff, E.type, Y.year,
				E.date_start as dateStart, E.date_end as dateEnd,
				UNIX_TIMESTAMP(E.date_start) as startTime, UNIX_TIMESTAMP(E.date_end) as endTime,
				C.name as courseName
			FROM exam E
				JOIN course C ON (C.id = E.id_course)
				JOIN universityyear Y ON (Y.id = E.id_year)
			WHERE C.id_univ = :univId
				AND E.id = :examId
				AND E.id_year = :yearId',
			[
				'univId' => $req->user->universityId,
				'yearId' => $yearId,
				'examId' => $examId
			]
		)->fetchObject();

		$hasAccess = $overview && (!$isStudent || ($now >= $overview->dateStart && $now <= $overview->dateEnd));

		if(!$hasAccess) {
			throw new ApiException(403, 'pas acces');
		}

		if($isStudent) {
			$studentLevel = $db->query(
				'SELECT id_level
				FROM student
				WHERE id = :studentId',
				[
					'studentId' => $req->user->studentId
				]
			)->fetchColumn();

			$levels = $db->query(
				'SELECT id_exam
				FROM examlevel
				WHERE id_exam = :examId
					AND id_level = :levelId',
				[
					'examId' => $examId,
					'levelId' => $studentLevel
				]
			)->fetch();

			if(!$levels) {
				throw new ApiException(403, 'mauvais niveau');
			}
		}

		$questions = ExamenService::getQuestionsFromExam($db, $req->user, $examId);

		$chapters = $db->query(
			'SELECT DISTINCT(C.label) as label, C.id
			FROM coursechapter C
				LEFT JOIN question Q ON (Q.id_chapter = C.id)
				LEFT JOIN examquestion EQ ON (EQ.id_question = Q.id)
			WHERE EQ.id_exam = :examId',
			[
				'examId' => $examId
			]
		)->fetchAll(PDO::FETCH_ASSOC);

		$this->data = (object)array(
			'overview' => $overview,
			'chapters' => $chapters,
			'questions' => $questions
		);
	}

	protected function modifyExam($req, $res) {
		$db = $res->getDatabase();
		$examId = $req->params['examId'];


		$r = $res->getDatabase()->query(
            'SELECT TT.id_teacher as teacherId, E.type as examType, E.date_start as dateStart
            FROM exam E
                INNER JOIN course C ON (C.id = E.id_course)
                LEFT JOIN teacherteaching TT ON (TT.id_course = C.id)
                LEFT JOIN universityyear Y ON (Y.id = TT.id_year)
            WHERE C.id_univ = :univId
				AND E.id = :examId
                AND Y.year = :year',
            [
                'univId' => $req->user->universityId,
                'examId' => $examId,
                'year' => $req->user->year
            ]
        )->fetch();

        if(!$r) {
            throw new ApiException(404);
        }

        if($r['teacherId'] !== $req->user->teacherId || $r['dateStart'] <= date("Y-m-d H:i:s")) {
            throw new ApiException(403);
        }



		$fields = [
			'name' => 'name',
			'coeff' => 'coeff',
			'dateStart' => 'date_start',
			'dateEnd' => 'date_end'
		];

		$examType = $req->body['type']?? $r['examType'];

		switch($examType) {
			case ExamType::CC:
			case ExamType::CI:
			case ExamType::CF:
				break;
			default:
				throw new ApiException(400, 'Unknown exam type');
		}

		$values = '';
		$queryParams = [];

		$queryParams['examId'] = $examId;

		foreach($fields as $f => $c) {
			if(isset($req->body[$f])) {
				$queryParams[$f] = $req->body[$f];
				$values .= "$c = :$f,";
			}
		}

		$noModifDone = true;

		$len = mb_strlen($values);

		if($len > 0) {
			$noModifDone = false;

			if($values[$len-1] === ',') {
				$values = rtrim($values, ',');
			}

			$db->query(
				"UPDATE exam
				SET $values
				WHERE id = :examId",
				$queryParams
			);
		}

		if(isset($req->body['questions'])) {
			$questions = json_decode($req->body['questions']);

			$this->updateQuestions($db, $examId, $questions);

			$noModifDone = false;
		}

		if(isset($req->body['target'])) {
			try {
				$targets = json_decode($req->body['target']);

				if(isset($req->body['body'])) {
					unset($req->body['body']);
				}

				$db->query(
					'DELETE FROM examlevel WHERE id_exam = :examId',
					[
						'examId' => $examId
					]
				);

				$this->makeLevelExamAssociation($db, $examId, $targets);

				$noModifDone = false;
			}
			catch(ApiException $e) {
                throw $e;
            }
			catch(Exception $e) {
				throw new ApiException(500, 'Something went wrong updating exam participants');
			}
		}

		if($noModifDone) {
			throw new ApiException(400, 'Missing fields');
		}
	}

	protected function deleteExam($req, $res) {
		$yearId = $this->getYearId($req, $res);

		$r = $res->getDatabase()->query(
			'SELECT TT.id_teacher as teacherId, E.date_start as dateStart
			FROM exam E
				INNER JOIN course C ON (C.id = E.id_course)
				LEFT JOIN teacherteaching TT ON (TT.id_course = C.id)
			WHERE C.id_univ = :univId
				AND TT.id_year = :yearId
				AND E.id = :examId',
			[
				'univId' => $req->user->universityId,
				'yearId' => $yearId,
				'examId' => $req->params['examId']
			]
		)->fetch();

		if(!$r) {
			throw new ApiException(404);
		}

		if($r['teacherId'] !== $req->user->teacherId || $r['dateStart'] <= date("Y-m-d H:i:s")) {
			throw new ApiException(403);
		}

        $res->getDatabase()->query(
			'DELETE FROM exam WHERE id = :examId',
			[
				'examId' => $req->params['examId']
			]
		)->fetchAll(PDO::FETCH_ASSOC);
	}



	/**
	 * Duplicate function of examGeneration
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
	protected function updateQuestions($db, $examId, $questions) {
		try {
			$posValues = [];
			$negValues = [];
			$params = [
				'examId' => $examId
			];

			$i = 0;

			foreach($questions as $qId => $scale) {
				array_push($posValues, "WHEN id_question = :questionId$i THEN :scale$i");
				array_push($negValues, "WHEN id_question = :questionId$i THEN :negScale$i");
				$params["questionId$i"] = $qId;
				$params["scale$i"] = $scale[0]?? NULL;
				$params["negScale$i"] = $scale[1]?? NULL;
				$i++;
			}

			$posValues = join(',', $posValues);
			$negValues = join(',', $negValues);

			$db->beginTransaction();
			$db->query(
				"UPDATE examquestion
				SET nb_points = CASE
					$posValues
				SET neg_points = CASE
					$negValues
				END
				WHERE id_exam = :examId",
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
}