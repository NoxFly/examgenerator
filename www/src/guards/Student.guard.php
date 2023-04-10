<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */


/*

This guard is designed for the pages of registered users of type STUDENT.

*/

defined('_NOX') or die('401 Unauthorized');

require_once(PATH_ENGINE_TEMPLATES . 'Guard.php');

class StudentGuard extends Guard {
    /**
     * @param stdClass $req
     * @param WebSite $req
     */
    public function canActivate($req, $res) {
        return bitwiseAND($_SESSION['privileges'], UserType::STUDENT);
    }
}