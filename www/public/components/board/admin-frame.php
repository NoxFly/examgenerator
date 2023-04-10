<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


$tabs = [
	'admin/university-management' => ['université', '/board/admin/university'],
	'admin/user-management' => ['utilisateurs', '/board/admin/users'],
	'admin/year-management' => ['années scolaires', '/board/admin/years'],
	'admin/cursus-management' => ['cursus', '/board/admin/cursus'],
	'admin/course-management' => ['matières', '/board/admin/courses']
];

$currentTab = $this->data['board'];

?>

<article class="board-frame">
	<div class="tabs">
		<ul>
<?php foreach($tabs as $v => $tab) { ?>

<?php

$class = '';

if($v === $currentTab) {
	$class = 'active';
}

?>
			<li class="<?=$class?>">
				<a href="<?=$this->url($tab[1])?>"><?=$tab[0]?></a>
			</li>
<?php } ?>
		</ul>
	</div>

	<div class="frame">
		<?php $this->includePage("board/$currentTab"); ?>
	</div>
</article>