<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


class Guard {
    function __construct() {

    }

    /**
     * @param stdClass $req
     * @param Site $res
     */
    public function canActivate($req, $res) {
        return true;
    }
}