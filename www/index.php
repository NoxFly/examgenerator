<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

require_once('../engine/definitions.php');

// pretty useless since we're including the definition just above
// but it's for security, if this include was removed in the future
defined('_NOX') or die('401 Unauthorized');



$config = parse_ini_file(PATH_CONF . SEP . 'config.ini', true);


require_once(PATH_SRC . SEP . 'Site.php');


$site = new WebSite($config);