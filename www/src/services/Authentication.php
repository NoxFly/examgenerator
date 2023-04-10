<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


class AuthenticationService
{
    protected $bLogged = false;

    function __construct()
    {
        session_start();
    }

    /**
     * @param WebSite $res
     * @param string $univ
     * @param string $domain
     * @param string $password
     * @return boolean
     */
    function register($res, $univ, $domain, $password)
    {
        try {
            $res->api()->fetch('PUT', 'university', [
                'name' => $univ,
                'domain' => $domain,
                'password' => $password
            ]);

            return true;
        }
        catch(ApiException $e) {
            return false;
        }
    }

    /**
     * @param WebSite $res
     * @param string $loginID
     * @param string $password
     * @return boolean
     */
    function login($res, $loginID, $password)
    {
        try {
            $data = $res->api()->fetch('POST', 'users/login', [
                'username' => $loginID,
                'password' => $password
            ]);
        }
        catch(ApiException $e) {
            return false;
        }

        // failed
        if(!$data) {
            return false;
        }


        // succeed
        $isAdmin = substr($loginID, 0, 6) === 'admin@';

        $_SESSION['id'] = $data['user']['accountId'];
        $_SESSION['lastConnDate'] = time();
        $_SESSION['privileges'] = $isAdmin? UserType::ADMIN : (isset($data['user']['teacherId'])? UserType::TEACHER : UserType::STUDENT);
        $_SESSION['token'] = $data['token'];
        $_SESSION['user'] = (object)$data['user'];

        $year = intval(date('Y'));

        if(intval(date('m')) < 8) {
            $year--;
        }

        $_SESSION['year'] = $year;

        $this->bLogged = true;

        return true;
    }

    /**
     * @param WebSite $res
     */
    function logout($res)
    {
        if($this->bLogged) {
            foreach($_SESSION as $k => $v) {
                unset($_SESSION[$k]);
            }

            try {
                $res->api()->fetch('DELETE', 'users/logout');
                $this->bLogged = false;
            }
            catch(ApiException $e) {
                throw $e;
            }
        }

        $_SESSION['privileges'] = UserType::ANONYMOUS;
    }

    /**
     * @param WebSite $res
     */
    public function refreshStatus($res) {
        if(!isset($_SESSION['token'])) {
            $this->logout($res);
            return;
        }

        $res->api()->setToken($_SESSION['token']);
        
        try {
            $result = $res->api()->fetch('GET', 'users/credentials/' . $_SESSION['id']);

            if(!$result || !isset($result['ok']) || $result['ok'] === false) {
                $this->logout($res);
                return;
            }

            $this->bLogged = true;
        }
        catch(ApiException $e) {
            $this->logout($res);
        }
    }

    public function isAuthenticated() {
        return $this->bLogged;
    }
}