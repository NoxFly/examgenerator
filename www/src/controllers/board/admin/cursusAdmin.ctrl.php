<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


require_once(PATH_ENGINE_TEMPLATES . 'Controller.php');
require_once(PATH_GUARDS . 'Admin.guard.php');


class CursusAdminController extends Controller {
    function __construct() {
        $this->guard = new AdminGuard();
    }

    /**
     * @param stdClass $req
     * @param WebSite $req
     */
    public function GET($req, $res) {
        $details = NULL;
        $list = [];

        try {
            // details
            if(isset($req->params['cursusId'])) {
                $id = $req->params['cursusId'];

                $cursus = $res->api()->fetch('GET', "cursus/$id");
                $levels = $res->api()->fetch('GET', "cursus/$id/levels");

                $details = [
                    'name' => $cursus['name'],
                    'levels' => $levels,
                    'level' => NULL
                ];
            }

            // list
            else {
                $list = $res->api()->fetch('GET', 'cursus');
            }
        }
        catch(ApiException $e) {
            $res->redirect("/{$e->getStatus()}");
        }

        $data = [
            'board' => 'admin/cursus-management',
            'list' => $list,
            'details' => $details,
            'pagination' => [
                'page' => 1,
                'maxPage' => 1,
                'maxResults' => count($list),
                'resultCount' => count($list)
            ]
		];

        $res->render('board/index', $data);
	}
}