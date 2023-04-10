<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');

?>

<h2>Création du barème</h2>

<div>
	<div id="marks-table">
		<button class="prev-step nohref text primary">&lt; étape précédente</button>
		<form></form>
		<p>Total: <input type="text" value="0" oninput="this.value = this.value.replace(/[^\d]/, '')" id="totalPoints"/> pts</p>
		<button class="nohref primary block btn-confirm" disabled>Confirmer</button>
	</div>
	<div id="subject-wrapper"></div>
</div>