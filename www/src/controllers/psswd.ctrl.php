<?php

/**
 * @copyright Copyrights (C) 2023 Arthur Gros All rights reserved.
 * @author Arthur Gros
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


require_once(PATH_ENGINE_TEMPLATES . 'Controller.php');
require_once(PATH_GUARDS . 'Psswd.guard.php');


class PsswdController extends Controller
{
    function __construct()
    {
        $this->guard = new PsswdGuard();
    }

    /**
     * @param stdClass $req
     * @param WebSite $req
     */
    public function GET($req, $res)
    {
        switch($req->page)
        {
            case 'user/forgot-password':
                return $res->render('auth/forgot-password');
            case 'user/reset-password':
                return $res->render('auth/reset-password');
        }
    }

    /**
     * @param stdClass $req
     * @param WebSite $req
     */
    public function POST($req, $res)
    {
        $status = 401;
        $data = (object)array(
            'ok' => false,
        );

        $oldPass = $req->body['oldpasswd'];
        $newPass = $req->body['newpasswd1'];

        try {
            $result = $res->api()->fetch('POST', '/users/'.$_SESSION['user']->id, [
                "oldPassword" => $oldPass,
                "newPassword" => $newPass 
            ]);

            if($result !== false && !isset($result['status'])) {
                $status = 200;
                $data->ok = true;
            }

            $res->status($status)->json($data);
        }
        catch(ApiException $e) {
            $e->sendJSON($res);
        }
    }

    /**
     * @param stdClass $req
     * @param WebSite $req
     */
    public function PUT($req, $res)
    {
        $status = 401;
        $data = [
            'ok' => false,
        ];

        $mail = $req->body['mail']?? NULL;

        if ($mail)
        {
            try {
                $result = $res->api()->fetch('PUT', 'users/forgot-password', [
                    "mail" => $mail
                ]);
                
                $status = 200;
                $data = $result;
            }
            catch(ApiException $e) {
                // nothing to do
            }
        }

        $res->status($status)->json($data);
    }


}
