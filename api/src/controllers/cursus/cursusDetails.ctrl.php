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


class CursusDetailsController extends ApiController {
	function __construct() {
        $this->guard = new ApiPrivilegesGuard([
            'GET' 		=> UserType::ADMIN | UserType::TEACHER | UserType::STUDENT,
			'POST' 		=> UserType::ADMIN,
			'PUT' 		=> UserType::ADMIN,
			'DELETE' 	=> UserType::ADMIN
        ]);
    }

	/**
	 * Renvoie les détails de l'overview d'un cursus.
	 * Si le cursus n'existe pas, code 404.
	 * 
	 * Codes de retour possibles :
	 * - 200 OK
	 * - 401 Utilisateur non authentifié
	 * - 404 Cursus inconnu
	 * - 500 Erreur pendant l'exécution de la requête dans la base de données
	 */
	public function GET($req, $res) {
		$this->sendData($req, $res, 'getCursus');
	}

	/**
     * Modifie les détails d'un cursus.
	 * Permet de modifier uniquement son nom.
	 * Si le cursus n'existe pas, code 404.
	 * 
	 * Codes de retour possibles :
     * - 200 OK
	 * - 400 Champs manquants
	 * - 401 Utilisateur non authentifié
	 * - 500 Erreur pendant l'exécution de la requête dans la base de données, ou le cursus n'existe pas
	 * 
     * @param stdClass $req
     * @param ApiSite $res
     */
	public function POST($req, $res) {
        $this->sendData($req, $res, 'modifyCursus');
	}

	/**
     * Créer un nouveau cursus au sein de l'université.
	 * Si le cursu a un nom qui est déjà pris, code 500
	 * 
	 * Codes de retour possibles :
     * - 200 OK
	 * - 400 Champs manquants
	 * - 401 Utilisateur non authentifié
	 * - 500 Erreur pendant l'exécution de la requête dans la base de données, ou nom déjà pris
	 * 
     * @param stdClass $req
     * @param ApiSite $res
     */
	public function PUT($req, $res) {
		$this->sendData($req, $res, 'createCursus');
	}

	/**
     * Supprime un cursus donné, et tout ce qui est lié.
	 * Si le cursus n'existe pas, code 404.
	 * 
	 * Codes de retour possibles :
     * - 200 OK
	 * - 401 Utilisateur non authentifié
	 * - 404 Le cursus n'existe pas
	 * - 500 Erreur pendant l'exécution de la requête dans la base de données
	 * 
     * @param stdClass $req
     * @param ApiSite $res
     */
	public function DELETE($req, $res) {
		$this->sendData($req, $res, 'deleteCursus');
	}

	
    /* -------------------------------------------------------- */


	protected function getCursus($req, $res) {
		$data = $res->getDatabase()->query(
			'SELECT id, name
			FROM universitycourse
			WHERE id_univ = :univId
				AND id = :cursusId',
			[
				'univId' => $req->user->universityId,
				'cursusId' => $req->params['cursusId']
			]
		)->fetchObject();

		if(!$data) {
			throw new ApiException(404);
		}

		$this->data = $data;
	}

	protected function modifyCursus($req, $res) {
		if(!isset($req->body['name'])) {
			throw new ApiException(400);
		}

		$cursusId = intval($req->params['cursusId']);
		$name = trim($req->body['name']);

		if(mb_strlen($name) === 0) {
			throw new ApiException(400);
		}

        $res->getDatabase()->query(
			'UPDATE universitycourse
			SET name = :name
			WHERE id = :cursusId
				AND id_univ = :univId',
			[
				'univId' => $req->user->universityId,
				'cursusId' => $cursusId,
				'name' => $name
			]
		);
	}

	protected function createCursus($req, $res) {
		if(!isset($req->body['name'])) {
			throw new ApiException(400);
		}

		$name = trim($req->body['name']);

		if(mb_strlen($name) === 0) {
			throw new ApiException(400);
		}

        $res->getDatabase()->query(
			'INSERT INTO universitycourse (id_univ, name)
				VALUES (:univId, :name)',
			[
				'univId' => $req->user->universityId,
				'name' => $name
			]
		)->fetchAll(PDO::FETCH_ASSOC);

		$this->data['cursusId'] = $res->getDatabase()->getLastInsertedId();
	}

	protected function deleteCursus($req, $res) {
		$r = $res->getDatabase()->query(
			'SELECT id
			FROM universitycourse
			WHERE id_univ = :univId
				AND id = :cursusId',
			[
				'univId' => $req->user->universityId,
				'cursusId' => $req->params['cursusId']
			]
		)->fetch();

		if(!$r) {
			throw new ApiException(404);
		}

        $res->getDatabase()->query(
			'DELETE FROM universitycourse
			WHERE id = :cursusId',
			[
				'cursusId' => $req->params['cursusId']
			]
		);
	}
}