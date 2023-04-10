<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


require_once(PATH_ENGINE_TEMPLATES . 'Controller.php');


class ErrorsController extends Controller {
    /**
     * @param stdClass $req
     * @param WebSite $req
     */
    public function GET($req, $res) {
        $status = $req->page;
        $message = 'Une erreur est survenue';

        switch($status) {
            case '404': $message = 'Vous vous êtes perdu en cherchant la salle K 107'; break;
        }

        $res->render("errors/error", [
            'statusCode' => $status,
            'message' => $message
        ]);
    }
}