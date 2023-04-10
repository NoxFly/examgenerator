<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


require_once(PATH_TEMPLATES . 'ApiController.php');
require_once(PATH_ENGINE_TEMPLATES . 'Guard.php');


class DevController extends ApiController {
    function __construct() {
        $this->guard = new Guard();
    }

    /**
     * Montre l'arbre du routeur en détail :
     * - Chaque noeud
     * - Si ce noeud a ou non un controlleur
     * - Quelle méthodes ce noeud supporte
     * 
     * @param stdClass $req
     * @param ApiSite $res
     */
    public function GET($req, $res) {
        if($req->env !== 'development') {
            $res->status(404)->send('');
        }

        $res->json($res->getRouter()->getRoutes());
    }
}