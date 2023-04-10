<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


class ApiException extends Exception {
	protected $method = NULL;
	protected $url = NULL;
	protected $details = NULL;

	/**
	 * @param int $status
	 * @param string $message
	 * @param mixed $details
	 */
	function __construct($method, $url, $status, $message, $details=NULL) {
		parent::__construct($message, $status);

		$this->method = $method;
		$this->url = $url;
		$this->details = $details;

		$this->file = "$method $url";
	}

	public function getMethod() {
		return $this->method;
	}

	public function getUrl() {
		return $this->url;
	}

	public function getDetails() {
		return $this->details;
	}

	public function getStatus() {
		return $this->code;
	}

	public function toJSON() {
		$json = [
			'status_code' => $this->code,
			'message' => $this->message
		];

		if($this->details !== NULL) {
			$json['details'] = $this->details;
		}

		return [
			'status' => $json
		];
	}

	/**
	 * @param WebSite $res
	 */
	public function sendJSON($res) {
		$res->status($this->code)->json($this->toJSON());
	}

	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}
}


class ApiService {
	protected $baseUri = '/';
	protected $baseUrl = 'api/';
	/** @var string $token */
	protected $token = NULL;
	
	/**
	 * @param string $baseAppUri
	 */
	function __construct($baseAppUri) {
		$this->baseUri = preg_replace('/^((?:(?:https?)?:\/\/)?(?:[a-zA-Z0-9\-\/\.]+)\/)[a-zA-Z0-9\-]+\/?$/', '$1', $baseAppUri);
	}

	private function prepareHeaders($headers) {
		$flattened = array();
	  
		foreach($headers as $key => $header) {
		  	if (is_int($key)) {
				$flattened[] = $header;
		  	} else {
				$flattened[] = $key.': '.$header;
			}
		}
	  
		return implode("\r\n", $flattened);
	}

	public function getBaseUri() {
		return $this->baseUri . $this->baseUrl;
	}

	/**
	 * @param string $method
	 * @param string $endpointUrl
	 * @param array $body
	 * @return array
	 */
	public function fetch($method, $endpointUrl, $body=[]) {
		if($method !== 'GET' && $method !== 'POST' && $method !== 'PUT' && $method !== 'DELETE') {
			throw new Exception('Method Not Allowed');
		}

		$headers = array(
			'Content-type: application/json'
		);

		if($this->token !== NULL) {
			array_push($headers, 'X-Auth-Token: ' . $this->token);
		}

		$options = array(
			'http' => array(
				'method' => $method,
				'ignore_errors' => true
			)
		);

		if($method === 'POST' || $method === 'PUT') {
			$content = http_build_query($body);
			$options['http']['content'] = $content;
		}

		$options['http']['header'] = $this->prepareHeaders($headers);

		$context  = stream_context_create($options);
		$url = $this->getBaseUri() . $endpointUrl;

		$res = @file_get_contents($url, false, $context);

		try {
			$res = json_decode($res, true);
		}
		catch(Exception $error) {
			throw new Exception('Failed to decode JSON from response.\n' . $res);
		}

		if(array_key_exists('status', $res)) {
			$status = $res['status']['status_code']?? 0;
			$message = $res['status']['message']?? '';
			$details = $res['status']['details']?? NULL;

			throw new ApiException($method, $url, $status, $message, $details);
		}

		return $res;
	}

	/**
	 * @param string $token
	 */
	public function setToken($token) {
		$this->token = $token;
	}
}