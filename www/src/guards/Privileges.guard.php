<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


require_once(PATH_ENGINE_TEMPLATES . 'Guard.php');


class PrivilegesGuard extends Guard {
    /**
     * @param mixed $privileges
     */
    protected $privileges;

    /**
     * Eeach parameter is a privilege for the associated method.
     * The privilege must be an integer from :
     * - 0 : none
     * - 1 : anonymous
     * - 2 : student
     * - 4 : teacher
     * - 8 : admin
     * @param int $pGET
     * @param int $pPOST
     * @param int $pPUT
     * @param int $pDELETE
     */
    function __construct($pGET, $pPOST=NULL, $pPUT=NULL, $pDELETE=NULL) {
        if($pPOST === NULL && $pPUT === NULL && $pDELETE === NULL) {
            $pPOST = $pGET;
            $pPUT = $pGET;
            $pDELETE = $pGET;
        }

        $this->privileges = [
            'GET' => $pGET,
            'POST' => $pPOST,
            'PUT' => $pPUT,
            'DELETE' => $pDELETE
        ];
    }

    /**
     * For now does not take care of anonymous user type.
     * Only for registered users.
     */
    public function canActivate($req, $res) {
        $prvlg = $this->privileges[$req->method];

        return bitwiseAND($prvlg, $_SESSION["privileges"]);
    }
}