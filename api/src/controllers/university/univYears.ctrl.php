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


class UnivYearsController extends ApiController {
	function __construct() {
        $this->guard = new ApiPrivilegesGuard([
            'GET' => UserType::ADMIN | UserType::TEACHER | UserType::STUDENT,
			'PUT' => UserType::ADMIN
        ]);
    }

	/**
     * Affiche la liste des années universitaires créées pour cette université.
	 * 
	 * Codes de retour possibles :
     * - 200
	 * - 401 Utilisateur non authentifié
	 * 
     * @param stdClass $req
     * @param ApiSite $res
     */
    public function GET($req, $res) {
        $this->sendData($req, $res, 'getUnivYears');
    }

	/**
     * Créé une nouvelle année université pour cette université.
	 * Il est impossible de modifier ou de supprimer l'année une fois créée.
	 * Si aucune année n'est passée en paramètre, alors l'année courante
	 * est utilisée par défaut.
	 * 
	 * Une année scolaire est définie par l'année lors du mois de la rentrée.
	 * Par exemple, si la rentrée se fait en septembre 2022 pour l'année 2022/2023,
	 * alors l'année est 2022.
	 * 
	 * note : Pour l'instant, les années universitaires, bien que ce soient
	 * les mêmes (2022, 2023, ...) ne sont pas partagées entre les
	 * université dans la base. Sujet à modification pour optimisation.
	 * 
	 * Codes de retour possibles :
     * - 200 OK
	 * - 401 Utilisateur non authentifié
	 * - 406 L'année existe déjà
	 * 
     * @param stdClass $req
     * @param ApiSite $res
     */
	public function PUT($req, $res) {
        $this->sendData($req, $res, 'createUnivYear');
	}


    /* -------------------------------------------------------- */


	protected function getUnivYears($req, $res) {
		$this->data = $res->getDatabase()->query(
			'SELECT year
			FROM universityyear
			WHERE id_univ = :univId
			ORDER BY year DESC',
			[
				'univId' => $req->user->universityId
			]
		)->fetchAll(PDO::FETCH_COLUMN);
	}

	protected function createUnivYear($req, $res) {
		$year = $req->body['year']?? NULL;

		if(!$year) {
			throw new ApiException(400, 'Missing fields');
		}

		$year = trim($year);

		if(mb_strlen($year) === 0) {
			throw new Exception(400);
		}

		$year = intval($year);

		if($year < 1900) {
			throw new Exception(400);
		}

		try {
			$res->getDatabase()->query(
				'INSERT INTO universityyear (id_univ, year)
					VALUES (:univId, :year)',
				[
					'univId' => $req->user->universityId,
					'year' => $year
				]
			);
		}
		catch(Exception $e) {
			throw new ApiException(406);
		}
	}
}