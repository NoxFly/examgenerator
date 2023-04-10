<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


require_once(PATH_ENGINE_TEMPLATES . 'Controller.php');


class AsideController extends Controller {
    /**
     * @param stdClass $req
     * @param WebSite $req
     */
    public function GET($req, $res) {
        $res->render("aside/$req->page");
    }
}