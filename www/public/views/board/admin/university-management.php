<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');

?>

<section class="view">
	<div>
		<div class="univ-picture"></div>
		<div class="univ-overview">
			<h2><?=$_SESSION['user']->universityName?></h2>
			<h6>@<span><?=$this->data['university']['domain']?></span></h6>
		</div>
	</div>
</section>