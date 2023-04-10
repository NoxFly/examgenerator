<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


require_once(PATH_GUARDS . 'API.guard.php');


class ApiPrivilegesGuard extends ApiGuard {
    /**
     * @param mixed $privileges
     */
    protected $privileges = [
        'GET'       => UserType::NONE,
        'POST'      => UserType::NONE,
        'PUT'       => UserType::NONE,
        'DELETE'    => UserType::NONE,
        'PATCH'     => UserType::NONE,
        'OPTIONS'   => UserType::NONE,
        'HEAD'      => UserType::NONE
    ];

    /**
     * Eeach parameter is a privilege for the associated method.
     * The privilege must be an integer from :
     * - 0 : none
     * - 1 : anonymous
     * - 2 : student
     * - 4 : teacher
     * - 8 : admin
     * @param int|array<int> $methods
     */
    function __construct($methods) {
        $type = gettype($methods);

        if($type === 'integer') {
            foreach(['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'] as $m => $v) {
                $this->privileges[$v] = $methods;
            }
        }
        else if($type === 'array') {
            $pL = count($this->privileges);
            $mL = count($methods);

            $a = $this->privileges;
            $b = $methods;
            
            if($mL < $pL) {
                $a = $methods;
                $b = $this->privileges;
            }

            foreach($a as $m => $v) {
                if(array_key_exists($m, $b)) {
                    $this->privileges[$m] = $methods[$m];
                }
            }
        }
    }

    /**
     * For now does not take care of anonymous user type.
     * Only for registered users.
     */
    public function canActivate($req, $res) {
        $prvlg = $this->privileges[$req->method];

        if(!bitwiseAND($prvlg, UserType::ANONYMOUS)) {
            return parent::canActivate($req, $res)
                && bitwiseAND($prvlg, $req->user->privileges);
        }

        return bitwiseAND($prvlg, $req->user->privileges);
    }
}