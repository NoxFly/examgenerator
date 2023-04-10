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


class CursusLevelDetailsController extends ApiController {
	function __construct() {
        $this->guard = new ApiPrivilegesGuard([
            'GET' 		=> UserType::ADMIN | UserType::TEACHER | UserType::STUDENT,
			'POST' 		=> UserType::ADMIN,
			'PUT' 		=> UserType::ADMIN,
			'DELETE' 	=> UserType::ADMIN
        ]);
    }

	/**
     * Affiche les détails d'un niveau d'un cursus au sein de l'université.
	 * - Affiche le nom et l'id du cursus
	 * - Affiche le nom et l'id du niveau
	 * - Affiche la liste des matières liées à ce niveau (paire <id, nom>[])
	 * 
	 * Codes de retour possibles :
     * - 200 OK
	 * - 401 Utilisateur non authentifié
	 * - 404 Le niveau n'existe pas
	 * 
     * @param stdClass $req
     * @param ApiSite $res
     */
    public function GET($req, $res) {
        $this->sendData($req, $res, 'getCursusLevel');
    }

	/**
     * Modifie le niveau donné.
	 * Permet de changer son nom, et la liste des matières associées.
	 * Erreur 500 si son nom est déjà pris par un autre.
	 * cursus dans la même université.
	 * 
	 * Codes de retour possibles :
     * - 200 OK
	 * - 400 Champs manquants
	 * - 401 Utilisateur non authentifié
	 * - 404 Le cursus niveau n'existe pas
	 * - 500 Erreur pendant l'exécution de la requête dans la base de données, ou le nom est déjà pris
	 * 
     * @param stdClass $req
     * @param ApiSite $res
     */
	public function POST($req, $res) {
        $this->sendData($req, $res, 'modifyCursusLevel');
    }

	/**
     * Créer un nouveau niveau pour un cursus donné.
	 * Si le cursus n'existe pas, code 404.
	 * 
	 * Codes de retour possibles :
     * - 200 OK
	 * - 401 Utilisateur non authentifié
	 * - 400 Champs manquants
	 * - 404 Le cursus n'existe pas
	 * - 500 Erreur pendant l'exécution de la requête dans la base de données
	 * 
     * @param stdClass $req
     * @param ApiSite $res
     */
	public function PUT($req, $res) {
        $this->sendData($req, $res, 'createCursusLevel');
	}

	/**
     * Supprime un niveau donné, et tout ce qui est lié.
	 * Doit préciser de quel cursus.
	 * Si le cursus ou le niveau n'existe pas, code 404.
	 * 
	 * Codes de retour possibles :
     * - 200 OK
	 * - 401 Utilisateur non authentifié
	 * - 404 Le cursus ou le niveau n'existe pas
	 * - 500 Erreur pendant l'exécution de la requête dans la base de données
	 * 
     * @param stdClass $req
     * @param ApiSite $res
     */
	public function DELETE($req, $res) {
        $this->sendData($req, $res, 'deleteCursusLevel');
	}


    /* -------------------------------------------------------- */


	protected function getCursusLevel($req, $res) {
        $overview = $res->getDatabase()->query(
			'SELECT L.id, L.name as levelName,
				UC.id as cursusId, UC.name as cursusName
			FROM level L
				INNER JOIN universitycourse UC ON (UC.id = L.id_universitycourse)
			WHERE UC.id_univ = :univId
				AND UC.id = :cursusId
				AND L.id = :levelId',
			[
				'univId' => $req->user->universityId,
				'cursusId' => $req->params['cursusId'],
				'levelId' => $req->params['levelId']
			]
		)->fetchObject();

		if(!$overview) {
			throw new ApiException(404);
		}

		$this->data = $overview;

		try {
			$yearId = $this->getYearId($req, $res);

			$this->data->courses = $res->getDatabase()->query(
				'SELECT C.id, C.name
				FROM course C
					INNER JOIN courselevelyear CLY ON (CLY.id_course = C.id)
				WHERE CLY.id_level = :levelId
					AND CLY.id_year = :yearId',
				[
					'levelId' => $req->params['levelId'],
					'yearId' => $yearId
				]
			)->fetchAll(PDO::FETCH_ASSOC);
		}
		catch(Exception $e) {
			$this->data->courses = [];
		}
	}

	protected function modifyCursusLevel($req, $res) {
		$cursusId = $req->params['cursusId'];
		$levelId = $req->params['levelId'];

		$db = $res->getDatabase();

		$r = $db->query(
			'SELECT UC.id
			FROM universitycourse UC
				LEFT JOIN level L ON (L.id_universitycourse = UC.id)
			WHERE UC.id = :cursusId
				AND L.id = :levelId
				AND id_univ = :univId',
			[
				'univId' => $req->user->universityId,
				'cursusId' => $cursusId,
				'levelId' => $levelId
			]
		);

		if(!$r) {
			throw new ApiException(404);
		}

		if(isset($req->body['name'])) {
			$name = $req->body['name'];

			$db->query(
				'UPDATE level
				SET name = :name
				WHERE id_universitycourse = :cursusId
					AND id = :levelId',
				[
					'cursusId' => $cursusId,
					'levelId' => $levelId,
					'name' => $name
				]
			);
		}

		if(isset($req->body['courses'])) {
			$courseIds = $req->body['courses'];

			$addIds = $courseIds[0]?? [];
			$remIds = $courseIds[1]?? [];

			$aC = count($addIds);
			$rC = count($remIds);

			if($aC === 0 && $rC === 0) {
				throw new ApiException(400);
			}

			$a = $addIds;
			$b = $remIds;

			if($rC < $aC) {
				$a = $remIds;
				$b = $addIds;
			}

			foreach($a as $id) {
				if(in_array($id, $b)) {
					throw new ApiException(400, 'Same course id in both adding and removing arrays');
				}
			}

			$yearId = $this->getYearId($req, $res);

			$db->beginTransaction();

			try {
				if($aC > 0) {
					$values = '';

					foreach($addIds as $i => $id) {
						$values .= "($yearId, $levelId, $id)";

						if($i < $aC-1) {
							$values .= ',';
						}
					}

					$db->query(
						"INSERT INTO courselevelyear (id_year, id_level, id_course)
						VALUES $values"
					);
				}

				if($rC > 0) {
					$values = '(' . join(',', $remIds) . ')';

					$db->query(
						"DELETE FROM courselevelyear
						WHERE id_year = :yearId
							AND id_level = :levelId
							AND id_course IN $values",
						[
							'yearId' => $yearId,
							'levelId' => $levelId
						]
					);
				}

				$db->commit();
			}
			catch(ApiException $e) {
				$db->rollback();
				throw $e;
			}
		}
	}

	protected function createCursusLevel($req, $res) {
		if(!isset($req->body['name'])) {
			throw new ApiException(400);
		}

		$cursusId = trim($req->params['cursusId']);
		$name = trim($req->body['name']);

		if(mb_strlen($name) === 0 || mb_strlen($cursusId) === 0) {
			throw new ApiException(400);
		}

		$r = $res->getDatabase()->query(
			'SELECT id
			FROM universitycourse
			WHERE id = :cursusId
				AND id_univ = :univId',
			[
				'univId' => $req->user->universityId,
				'cursusId' => $cursusId
			]
		);

		if(!$r) {
			throw new ApiException(404);
		}

        $res->getDatabase()->query(
			'INSERT INTO level (id_universitycourse, name)
				VALUES (:cursusId, :name)',
			[
				'cursusId' => $cursusId,
				'name' => $name
			]
		);

		$this->data['levelId'] = $res->getDatabase()->getLastInsertedId();
	}

	protected function deleteCursusLevel($req, $res) {
		$r = $res->getDatabase()->query(
			'SELECT L.id
			FROM level L
				INNER JOIN universitycourse UC
			WHERE L.id = :levelId
				AND L.id_universitycourse = :cursusId
				AND UC.id_univ = :univId',
			[
				'univId' => $req->user->universityId,
				'cursusId' => $req->params['cursusId'],
				'levelId' => $req->params['levelId']
			]
		)->fetch();

		if(!$r) {
			throw new ApiException(404);
		}

        $res->getDatabase()->query(
			'DELETE FROM level
			WHERE id = :levelId
				AND id_universitycourse = :cursusId',
			[
				'cursusId' => $req->params['cursusId'],
				'levelId' => $req->params['levelId']
			]
		)->fetchAll(PDO::FETCH_ASSOC);
	}
}