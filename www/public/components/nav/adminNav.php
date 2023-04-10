<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');

?>


<ul>
	<li><a href="<?=$this->url('/board/admin/university')?>">Board</a></li>
</ul>


<div id="nav-right-actions">
	<button class="stroke danger round">
		<a href="<?=$this->url('/logout')?>">Se déconnecter</a>
	</button>
</div>