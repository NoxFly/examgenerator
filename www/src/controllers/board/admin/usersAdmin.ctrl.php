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


class UsersAdminController extends Controller {
    function __construct() {
        $this->guard = new AdminGuard();
    }

    /**
     * @param stdClass $req
     * @param WebSite $req
     */
    public function GET($req, $res) {
        $roles = ['teacher', 'student'];

        if(!isset($req->query['role']) || !in_array(($role = $req->query['role']), $roles)) {
            $res->redirect($req->page . '?role=' . $roles[0]);
        }

        if(!isset($req->query['page']) || !is_numeric($req->query['page'])) {
            $res->redirect($req->page . '?role=' . $role . '&page=1');
        }

        $page = intval($req->query['page']);

        $fields = [
            'years' => [],
            'cursus' => [],
            'levels' => []
        ];

        try {
            $fields['years'] = $res->api()->fetch('GET', 'university/years');
            $fields['cursus'] = $res->api()->fetch('GET', 'cursus');

            foreach($fields['cursus'] as $cursus) {
                $cursusId = $cursus['id'];
                $fields['levels'][$cursusId] = $res->api()->fetch('GET', "cursus/$cursusId/levels");
            }
        }
        catch(ApiException $e) {
            // nothing to do
        }

        $res->render('board/index', [
            'board' => 'admin/user-management',
            'fields' => $fields,
            'users' => [],
            'pagination' => [
                'page' => $page,
                'maxPage' => 1,
                'maxResults' => 0,
                'resultCount' => 0
            ]
        ]);
    }
}