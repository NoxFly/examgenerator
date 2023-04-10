<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


require_once(PATH_ENGINE_TEMPLATES . 'Controller.php');
require_once(PATH_GUARDS . 'Privileges.guard.php');


class MyCoursesController extends Controller {

    function __construct() {
        $this->guard = new PrivilegesGuard(
            UserType::TEACHER | UserType::STUDENT,
            UserType::NONE,
            UserType::NONE,
            UserType::NONE
        );
    }

    /**
     * @param stdClass $req
     * @param WebSite $req
     */
    public function GET($req, $res) {
        $courses = $res->api()->fetch("GET", "/courses");

        $res->render("board/course/myCourses", ['courses' => $courses]);
    }
}