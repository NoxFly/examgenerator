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


class CoursesAdminController extends Controller {
    function __construct() {
        $this->guard = new AdminGuard();
    }

    /**
     * @param stdClass $req
     * @param WebSite $req
     */
    public function GET($req, $res) {
        $fields = [
            'years' => [],
            'cursus' => [],
            'teachers' => []
        ];

        $courses = [];

        try {
            $fields['cursus'] = $res->api()->fetch('GET', 'cursus');
            $fields['years'] = $res->api()->fetch('GET', 'university/years');
            $fields['teachers'] = $res->api()->fetch('GET', 'users/byRole/teacher');
            $courses = [];//$res->api()->fetch('GET', 'courses');
        }
        catch(ApiException $e) {
            $res->redirect("/{$e->getStatus()}");
        }

		$res->render('board/index', [
            'board' => 'admin/course-management',
            'fields' => $fields,
            'courses' => $courses,
            'pagination' => [
                'page' => 1,
                'maxPage' => 1,
                'maxResults' => count($courses),
                'resultCount' => count($courses)
            ]
		]);
	}
}