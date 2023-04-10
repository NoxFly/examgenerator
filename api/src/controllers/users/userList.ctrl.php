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


class UserListController extends ApiController {
    function __construct() {
        $this->guard = new ApiPrivilegesGuard([
            'GET' => UserType::ADMIN | UserType::TEACHER | UserType::STUDENT
        ]);
    }

    /**
     * Retourne la liste des utilisateurs de l'université.
     * --> Ne liste pas les comptes administrateurs.
     * Si un rôle est passé en paramètre, n'affiche que les
     * utilisateurs ayant ce rôle.
     * Si le rôle n'existe pas, code 404.
     * 
     * Codes de retour possibles :
     * - 200 OK
     * - 400 Le role donné n'existe pas
     * - 401 Utilisateur non authentifié
     * 
     * @param stdClass $req
     * @param ApiSite $res
     */
    public function GET($req, $res) {
        $this->sendData($req, $res, 'getList');
    }


    /* -------------------------------------------------------- */


    protected function getList($req, $res) {
        $from = '';
        $slct = '';

        if(isset($req->params['role'])) {
            $from = 'INNER JOIN ';

            if($req->params['role'] === 'teacher') {
                $from .= 'teacher T ON (T.id_user = U.id)';
                $slct = ', T.id as teacherId';
            }
            else if($req->params['role'] === 'student') {
                $from .= 'student S ON (S.id_user = U.id)';
                $slct = ', S.id as studentId';
            }
            else {
                throw new ApiException(400);
            }
        }

        $this->data = $res->getDatabase()->query(
            "SELECT U.id as userId, U.uuid as userUUID,
                A.mail as userMail $slct
            FROM user U
            LEFT JOIN account A ON (U.id_account = A.id)
            $from
            WHERE A.id_univ = :univId",
            [
                'univId' => $req->user->universityId
            ]
        )->fetchAll(PDO::FETCH_OBJ);
    }
}