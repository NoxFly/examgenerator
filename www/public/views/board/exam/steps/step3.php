<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');

?>

<h2>Choix des niveaux et dates</h2>

<div class="select multiple slct-levels">
	Sélectionnez les niveaux qui passeront l'examen
<?php foreach($this->data['levels'] as $level) { ?>
	<opt data-value="<?=$level['id']?>"><?=$level['name']?> <?=$level['cursusName']?></opt>
<?php } ?>
</div>

<div class="dates-wrapper">
	<div>
		<label for="date-start">Date de début</label>
		<input type="datetime-local" id="date-start">
	</div>
	<div>
		<label for="date-end">Date de fin</label>
		<input type="datetime-local" id="date-end">
	</div>
</div>

<div class="actions">
	<button id="btn-cancel" class="nohref medium prev-step">Retour</button>
	<button id="btn-create-exam" class="nohref success">Créer l'examen</button>
</div>


<section class="popup create-popup hidden">
	<div></div>
</section>