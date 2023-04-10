<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');



class ApiException extends Exception {
	function __construct($status, $message=NULL) {
		parent::__construct($message, $status);
	}

	public function getStatus() {
		return $this->code;
	}
}


class APIService
{
	function __construct()
	{
		
	}

	/**
	 * Return a common and uniform response object.
	 * 
	 * @param int $statusCode
	 * @param mixed $details
	 */
	private function getObjectFromStatus($statusCode, $details=NULL) {
		$data = [
			'status' => [
				'status_code' => $statusCode
			]
		];

		if(array_key_exists($statusCode, ERRORS)) {
			$data['status']['message'] = ERRORS[$statusCode];
		}

		if(isset($details)) {
			$data['status']['details'] = $details;
		}

		return $data;
	}

	/**
	 * Returns status 200 in everycases so it can send the uniform object
	 * depending the given status. Can also give a detailed message.
	 * @param ApiSite $res
	 * @param int $status
	 * @param mixed $details
	 */
	public function sendErrorResponse($res, $status, $details=NULL) {
		$data = $this->getObjectFromStatus($status, $details);
		$res->status($status)->json($data);
		die();
	}
}