<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');

$this->includeJS('board/exam/review');

?>

<h1>Correction d'un examen</h1>

<div id="subject-actions">
	<button id="btn-save" class="primary nohref" disabled>Enregistrer</button>
	<button id="btn-next" class="success nohref" disabled>Confirmer et passer à la question suivante</button>
</div>

<div id="subject-wrapper"></div>

<section id="op-popup" class="popup hidden">
	<div></div>
</section>