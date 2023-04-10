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


class CursusLevelListController extends ApiController {
    function __construct() {
        $this->guard = new ApiPrivilegesGuard([
            'GET' => UserType::ADMIN | UserType::TEACHER | UserType::STUDENT
        ]);
    }

    /**
     * Liste les niveaux d'un cursus donné au sein de l'université.
     * Code 404 si cursus pas trouvé.
     * Paire <id, nom>[]
     * 
     * Codes de retour possibles :
     * - 200 OK
     * - 401 Utilisateur non authentifié
	 * - 404 Le cursus n'existe pas
     * 
     * @param stdClass $req
     * @param ApiSite $res
     */
    public function GET($req, $res) {
        $this->sendData($req, $res, 'listCursusLevels');
    }


    /* -------------------------------------------------------- */


	protected function listCursusLevels($req, $res) {
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

        $this->data = $res->getDatabase()->query(
            'SELECT id, name
            FROM level
            WHERE id_universitycourse = :cursusId',
            [
                'cursusId' => $req->params['cursusId']
            ]
        )->fetchAll(PDO::FETCH_ASSOC);
	}
}