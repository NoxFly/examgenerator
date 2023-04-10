<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */


define('_NOX', TRUE);


define('SEP', DIRECTORY_SEPARATOR);

define('PATH_ROOT', 			realpath('.') . SEP);
// should fin a better way to realpath :
// instead, if not in the ../ of the index.php which
// includes this file, throws an error.
define('PATH_ENGINE', 			realpath('../engine') . SEP);
define('PATH_COMMON', 			realpath('../common') . SEP);

define('PATH_CONF', 			PATH_ROOT . 'conf' . SEP);
define('PATH_SRC', 				PATH_ROOT . 'src' . SEP);

define('PATH_CTRL', 			PATH_SRC . 'controllers' . SEP);
define('PATH_SERVICES', 		PATH_SRC . 'services' . SEP);
define('PATH_GUARDS', 			PATH_SRC . 'guards' . SEP);
define('PATH_TEMPLATES', 		PATH_SRC . 'templates' . SEP);

// views
define('PATH_PUBLIC', 			PATH_ROOT . 'public' . SEP);
define('PATH_ASSET', 			PATH_PUBLIC . 'asset' . SEP);
define('PATH_VIEWS', 			PATH_PUBLIC . 'views' . SEP);

// engine
define('PATH_ENGINE_GUARDS', 	PATH_ENGINE . 'guards' . SEP);
define('PATH_ENGINE_SERVICES', 	PATH_ENGINE . 'services' . SEP);
define('PATH_ENGINE_TEMPLATES', PATH_ENGINE . 'templates' . SEP);




define('ERRORS', [
	// Information responses
	100 => 'Continue',
	101 => 'Switching Protocol',
	102 => 'Processing',
	103 => 'Early Hints',
	// Successful responses
	200 => 'OK',
	201 => 'Created',
	202 => 'Accepted',
	203 => 'Non-Authoritative Information',
	204 => 'No Content',
	205 => 'Reset Content',
	206 => 'Partial Content',
	207 => 'Multi Status',
	208 => 'Already Reported',
	226 => 'IM Used',
	// Redirection Messages
	300 => 'Multiple Choices',
	301 => 'Moved Permanently',
	302 => 'Found',
	303 => 'See Other',
	304 => 'Not Modified',
	307 => 'Temporary Redirect',
	308 => 'Permanently Redirect',
	// Client Error Responses
	400 => 'Bad Request',
	401 => 'Unauthorized',
	402 => 'Payement Required',
	403 => 'Forbidden',
	404 => 'Not Found',
	405 => 'Method Not Allowed',
	406 => 'Not Acceptable',
	407 => 'Proxy Authentication Required',
	408 => 'Request Timeout',
	409 => 'Conflict',
	410 => 'Gone',
	411 => 'Length Required',
	412 => 'Precondition Failed',
	413 => 'Payload Too Large',
	414 => 'URI Too Long',
	415 => 'Unsupported Media Type',
	416 => 'Range Not Satisfiable',
	417 => 'Expectation Failed',
	418 => 'I\'m a Teapot',
	421 => 'Misdirected Request',
	422 => 'Unprocessable Content',
	423 => 'Locked',
	424 => 'Failed Dependency',
	425 => 'Too Early',
	426 => 'Upgrade Required',
	428 => 'Precondition Required',
	429 => 'Too Many Requests',
	431 => 'Request Header Fields Too Large',
	451 => 'Unavailable For Legal Reasons',
	// Server Error Responses
	500 => 'Internal Server Error',
	501 => 'Not Implemented',
	502 => 'Bad Gateway',
	503 => 'Service Unavailable',
	504 => 'Gateway Timeout',
	505 => 'HTTP Version Not Supported',
	506 => 'Variant Also Negotiates',
	507 => 'Insuficient Storage',
	508 => 'Loop Detected',
	510 => 'Not Extended',
	511 => 'Network Authentication Required'
]);




if(file_exists(PATH_COMMON . 'definitions.php')) {
	include_once(PATH_COMMON . 'definitions.php');
}