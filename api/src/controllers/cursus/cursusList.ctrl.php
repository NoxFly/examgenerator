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


class CursusListController extends ApiController {
    function __construct() {
        $this->guard = new ApiPrivilegesGuard([
            'GET' => UserType::ADMIN | UserType::TEACHER | UserType::STUDENT
        ]);
    }

    /**
     * Affiche la liste des cursus de l'université.
     * Paire <id, nom>[]
     * 
     * Codes de retour possibles :
     * - 200 OK
     * - 401 Utilisateur non authentifié
     * 
     * @param stdClass $req
     * @param ApiSite $res
     */
    public function GET($req, $res) {
        $this->sendData($req, $res, 'getCursus');
    }


    /* -------------------------------------------------------- */


	protected function getCursus($req, $res) {
        $this->data = $res->getDatabase()->query(
            'SELECT id, name
            FROM universitycourse
            WHERE id_univ = :univId',
            [
                'univId' => $req->user->universityId
            ]
        )->fetchAll(PDO::FETCH_ASSOC);
	}
}