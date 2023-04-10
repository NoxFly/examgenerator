<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');

?>

<h1>Erreur <?=$this->data['statusCode']?></h1>

<p class="center"><?=$this->data['message']?></p>

<div id="error-btn">
	<button class="primary">
		<a href="<?=$this->url('/')?>">Revenir à l'accueil</a>
	</button>
</div>