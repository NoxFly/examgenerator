<?php

/**
 * @copyright Copyrights (C) 2023 Jean-Charles Armbruster All rights reserved.
 * @author Jean-Charles Armbruster
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');

function getChunkName($chunk) {
	switch($chunk) {
		case 'asParticipant':
			return 'En tant que participant';
		case 'asReferent':
			return 'En tant que référent';
	}
}

?>

<h1>Mes cours</h1>

<article>
<?php foreach($this->data["courses"] as $as => $chunk) { ?>
	<?php if(count($chunk) === 0) continue; ?>
	<h2><?=getChunkName($as)?></h2>
	<ul class="list-<?=$as?>">
<?php foreach($chunk as $course) { ?>
		<li>
			<div></div>
			<p><?=$course['name']?></p>
			<a href="<?=$this->url('/board/course/'.$course['id'])?>"></a>
		</li>
<?php } ?>
	</ul>
<?php }?>
</article>