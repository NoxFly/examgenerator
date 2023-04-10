<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


if(array_key_exists('board', $this->data)) {
	$this->requireComponent('board/admin-frame.php');
}
else {
	// default view
}

?>