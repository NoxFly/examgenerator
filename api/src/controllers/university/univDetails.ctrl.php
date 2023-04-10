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


class UnivDetailsController extends ApiController {
    function __construct() {
        $this->guard = new ApiPrivilegesGuard([
            'GET' => UserType::ADMIN | UserType::TEACHER | UserType::STUDENT,
			'PUT' => UserType::ANONYMOUS
        ]);
    }

    /**
     * Renvoie les informations liées à l'université.
     * - Nom, domaine
     * - Nombre de cursus
     * - Nombre de matières
     * - Nombre d'enseignants
     * - Nombre d'étudiants
     * 
     * Codes de retour possibles :
     * - 200 OK
     * - 401 Utilisateur non authentifié
     * 
     * @param stdClass $req
     * @param ApiSite $res
     */
    public function GET($req, $res) {
        $this->sendData($req, $res, 'getUniv');
    }

    /**
     * Créé une nouvelle université.
     * Disponible uniquement pour un Utilisateur non enregistré.
     * Un utilisateur enregistré auprès d'une université ne peut en créer
     * une nouvelle.
     * 
     * Codes de retour possibles :
     * - 200 OK
     * - 400 Les informations pour créer l'université ne respectent pas la forme demandée
     * - 401 Utilisateur non anonyme
	 * - 500 Erreur pendant l'exécution de la requête dans la base de données
     * 
     * @param stdClass $req
     * @param ApiSite $res
     */
	public function PUT($req, $res) {
		$this->sendData($req, $res, 'createUniv');
	}


    /* -------------------------------------------------------- */


	protected function getUniv($req, $res) {
		$this->data = $res->getDatabase()->query(
			'SELECT U.name, U.domain,
				COUNT(DISTINCT UC.id) as cursusCount,
				COUNT(DISTINCT CLY.id_course) as courseCount,
				COUNT(DISTINCT T.id) as teachercount,
				COUNT(DISTINCT S.id) as studentcount
			FROM university U
				LEFT JOIN universityyear Y ON (Y.id_univ = U.id)
				LEFT JOIN universitycourse UC ON (UC.id_univ = U.id)
                LEFT JOIN level L ON (L.id_universitycourse = UC.id)
				LEFT JOIN courselevelyear CLY ON (CLY.id_year = Y.id)
				LEFT JOIN account A ON (A.id_univ = U.id)
				LEFT JOIN user USR ON (USR.id_account = A.id)
				LEFT JOIN teacher T ON (T.id_user = USR.id)
				LEFT JOIN student S ON (S.id_user = USR.id)
			WHERE U.id = :univId
                AND Y.year = :year',
			[
				'univId' => $req->user->universityId,
                'year' => $req->user->year
			]
		)->fetchObject();
	}

	protected function createUniv($req, $res) {
		if(!isset($req->body['name']) || !isset($req->body['domain']) || !isset($req->body['password'])) {
			throw new ApiException(400);
		}

		$name = trim($req->body['name']);
		$domain = trim($req->body['domain']);
		$password = trim($req->body['password']);

        if(mb_strlen($name) === 0 || mb_strlen($domain) === 0 || mb_strlen($password) === 0) {
            throw new ApiException(400);
        }

		$db = $res->getDatabase();

		try {
            // debut des modifications
            $db->beginTransaction();

            // on ajoute l'universite dans la bdd
            $db->query(
                'INSERT INTO university (name, domain)
                VALUES (:name, :domain)',
                [
                    'name' => $name,
                    'domain' => $domain
                ]
            );

            // on recupere l'id qu'on vient de creer
            $this->data['universityId'] = $db->getLastInsertedId();

            $res->auth()->createUnivAccount($this->data['universityId'], $domain, $password);

            $this->data['accountId'] = $db->getLastInsertedId();

            $db->commit();

        }
        catch(ApiException $e) {
            $db->rollback();
            throw $e;
        }
        catch(Exception $e) {
            $db->rollBack();
            throw new ApiException(500);
        }
	}
}