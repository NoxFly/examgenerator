<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


require_once(PATH_ENGINE_TEMPLATES . 'Controller.php');
require_once(PATH_GUARDS . 'Auth.guard.php');


class AuthController extends Controller {
    function __construct() {
        $this->guard = new AuthGuard();
    }

    /**
     * @param stdClass $req
     * @param WebSite $req
     */
    public function GET($req, $res) {
        switch($req->page) {
            case 'login':
                return $res->render('auth/login');
            case 'university/register':
                return $res->render('auth/university-register');
            case 'logout':
                return $this->DELETE($req, $res);
        }
    }

    /**
     * Function for LOGGING IN USER
     * @param stdClass $req
     * @param WebSite $res
     */
    public function POST($req, $res) {
        $status = 401;
        $data = (object)array(
            'ok' => false
        );

        if(isset($req->body['username']) && isset($req->body['password'])) {
            $result = $res->auth()->login($res, $req->body['username'], $req->body['password']);

            if($result !== false) {
                $data->ok = true;
                $status = 200;
            }
        }

        $res->status($status)->json($data);
    }

    /**
     * Function for REGISTERING UNIVERSITY
     * @param stdClass $req
     * @param WebSite $res
     */
    public function PUT($req, $res) {
        $status = 401;
        $data = (object)array(
            'ok' => false
        );

        
        if (isset($req->body['university']) && isset($req->body['domain']) && isset($req->body['password']))
        {
            $univ = $req->body['university'];
            $domain = $req->body['domain'];
            $password = $req->body['password'];

            $result = $res->auth()->register($res, $univ, $domain, $password);

            if ($result !== false)
            {
                $data->ok = true;
                $status = 200;
            }
        }

        $res->status($status)->json($data);
    }

    /**
     * Function for LOGGING OUT USER
     * @param stdClass $req
     * @param WebSite $res
     */
    public function DELETE($req, $res) {
        $res->auth()->logout($res);
        $res->redirect('/');
    }
}