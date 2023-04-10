<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


require_once(PATH_ENGINE_TEMPLATES . 'Controller.php');
require_once(PATH_GUARDS . 'API.guard.php');


abstract class ApiController extends Controller {
	protected $status = 200;
	protected $data = [];
	

	function __construct() {
		$this->guard = new ApiGuard();
	}

	/**
	 * @param stdClass $req
	 * @param ApiSite $res
	 * @param string $fn
	 */
	protected function sendData($req, $res, $fn) {
		try {
			if($req->method === 'POST' || $req->method === 'OPTIONS') {
				$this->status = 204;
			}
			else if($req->method === 'PUT') {
				$this->status = 201;
			}

			$this->{$fn}($req, $res);
			$res->status($this->status)->json($this->data);
		}
		catch(ApiException $e) {
			$res->api()->sendErrorResponse($res, $e->getStatus(), $e->getMessage());
		}
		catch(Exception $e) {
			$res->api()->sendErrorResponse($res, 500);
		}
	}

	/**
	 * Commonly used function by every controller
	 * @param stdClass $req
	 * @param ApiSite $res
	 */
	protected function getYearId($req, $res) {
		$year = NULL;
		$f = ($req->method === 'GET' || $req->method === 'DELETE')? 'query' : 'body';

		$year = $req->{$f}['year']?? $req->user->year;

		$yearId = $res->getDatabase()->query(
            'SELECT id
            FROM universityyear
            WHERE year = :year
                AND id_univ = :univId',
            [
                'year' => $year,
                'univId' => $req->user->universityId
            ]
        )->fetch()['id']?? NULL;

		if(!$yearId) {
            throw new ApiException(404);
        }

		return $yearId;
	}
}