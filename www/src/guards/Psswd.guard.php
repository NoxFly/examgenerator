<?php

/**
 * @copyright Copyrights (C) 2023 Arthur Gros All rights reserved.
 * @author Arthur Gros
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');

/*
    This guard is for the password, forgot et reset
*/

require_once(PATH_ENGINE_TEMPLATES . 'Guard.php');

class PsswdGuard extends Guard
{
    /**
     * @param stdClass $req
     * @param WebSite $req
     */
    public function canActivate($req, $res) 
    {
        if ($req->method === 'GET')
        {
            return ($req->page!=='user/reset-password' || $res->auth()->isAuthenticated());
        }
        else if($req->method === 'POST')
        {
            return $res->auth()->isAuthenticated();
        }
        
        return $req->method === 'PUT';

    }

}
