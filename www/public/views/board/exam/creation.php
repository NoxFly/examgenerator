<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');

?>

<div id="step-progress-bar">
	<div></div>
</div>

<h1>Nouvel Examen</h1>

<div id="step-content"></div>

<?php $this->includeJS('board/exam/new'); ?>