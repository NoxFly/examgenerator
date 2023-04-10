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


class UnivAdminController extends Controller {
    function __construct() {
        $this->guard = new AdminGuard();
    }

    /**
     * @param stdClass $req
     * @param WebSite $req
     */
    public function GET($req, $res) {
        try {
            $data = $res->api()->fetch('GET', 'university');
        }
        catch(ApiException $e) {
            $res->redirect("/{$e->getStatus()}");
        }

		$res->render('board/index', [
            'board' => 'admin/university-management',
            'university' => $data
		]);
	}
}