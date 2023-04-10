<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


require_once(PATH_TEMPLATES . 'ApiController.php');
require_once(PATH_GUARDS . 'Auth.guard.php');


/**
 * Controlleur 'ouvert' : possibilité de l'appeler sans token.
 * Porte d'entrée pour se connecter, ou vérifier si son token est toujours valable.
 */
class UserCredentialsController extends ApiController {
	function __construct() {
		$this->guard = new AuthGuard();
	}

	/**
     * Vérifie si le token de l'utilisateur dont l'identifiant passé dans le body
	 * est toujours valide ou non. Renvoie { ok: boolean }.
	 * Si le token a expiré, il est supprimé.
	 * 
	 * Codes de retour possibles :
     * - 200 OK
	 * 
     * @param stdClass $req
     * @param ApiSite $res
     */
	public function GET($req, $res) {
		$this->sendData($req, $res, 'verifyToken');
	}

	/**
     * Essaie de connecter l'utilisateur avec les identifiants renseignés.
	 * Si succès : code 200.
	 * Si échoué : code 401.
	 * 
	 * Codes de retour possibles :
     * - 200 OK
     * - 400 Les paramètres donnés pour se connecter ne respectent pas la forme demandée
	 * - 401 Idenfiants erronés
	 * 
     * @param stdClass $req
     * @param ApiSite $res
     */
    public function POST($req, $res) {
        $this->sendData($req, $res, 'login');
    }

	/**
	 * /!\ Dev Only
	 * Change le mot de passe de l'utilisateur anonyme ayant rentré
	 * son mail. Aucune vérification. Aucune sécurité. Soucis de dev
	 * en local.
	 * 
	 * Codes de retour possibles :
	 * - 200 OK
	 * - 400 Champs manquants
	 * - 403 Le mail n'existe pas
	 * - 500 Une erreur est survenue lors du changement de mot de passe
	 * 
	 * @param stdClass $req
	 * @param ApiSite $res
	 */
	public function PUT($req, $res) {
        $this->sendData($req, $res, 'forgotPassword');
    }

	/**
     * Déconnecte un utilisateur côté serveur.
	 * Supprime son token. Ne tient pas compte si plusieurs appareils
	 * sont connectés au même compte (déconnecte tout le monde).
	 * 
	 * Codes de retour possibles :
     * - 200 OK
	 * - 401 Utilisateur non authentifié
	 * 
     * @param stdClass $req
     * @param ApiSite $res
     */
	public function DELETE($req, $res) {
        $this->sendData($req, $res, 'logout');
	}


    /* -------------------------------------------------------- */


	protected function login($req, $res) {
		$this->status = 200;

		if(!isset($req->body['username']) || !isset($req->body['password'])) {
			throw new ApiException(400);
		}

		$login = $req->body['username'];
		$password = $req->body['password'];

		//

		if(($this->data = $res->auth()->login($login, $password)) === NULL) {
			throw new ApiException(401);
		}
	}

	protected function logout($req, $res) {
		$token = $res->auth()->getToken($req->query);

		if($token) {
			$res->auth()->logoutByToken($token);
		}
	}

	protected function verifyToken($req, $res) {
		$this->data = [
			'ok' => $res->auth()->checkTokenValidity($req->params['id'], $req->body)
		];
	}

	protected function forgotPassword($req, $res)
	{
		$mail = $req->body['mail']?? NULL;

		if(!$mail) {
			throw new ApiException(400);
		}

		$accountId = $res->getDatabase()->query(
			'SELECT id FROM `account`
			WHERE `mail` = :mail',
			[
				'mail' => $mail
			]
		)->fetchColumn();

		if (!$accountId)
		{
			throw new ApiException(403);
		}

		$this->data['password'] = $res->auth()->changePassword($accountId);
	}
}