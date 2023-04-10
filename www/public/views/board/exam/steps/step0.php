<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');

?>

<h2>Choix de la matière</h2>

<div class="select" id="slct-course">
	Sélectionner une matière
<?php foreach($this->data['courses'] as $course) { ?>
	<opt data-value="<?=$course['id']?>"><?=$course['name']?></opt>
<?php } ?>
</div>