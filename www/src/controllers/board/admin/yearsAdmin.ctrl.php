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


class YearsAdminController extends Controller {
    function __construct() {
        $this->guard = new AdminGuard();
    }

    /**
     * @param stdClass $req
     * @param WebSite $req
     */
    public function GET($req, $res) {
        $years = [];

        try {
            $years = $res->api()->fetch('GET', 'university/years');
        }
        catch(ApiException $e) {
            $res->redirect("/{$e->getStatus()}");
        }

        $res->render('board/index', [
            'board' => 'admin/year-management',
            'years' => $years,
            'pagination' => [
                'page' => 1,
                'maxPage' => 1,
                'maxResults' => count($years),
                'resultCount' => count($years)
            ]
        ]);
	}
}