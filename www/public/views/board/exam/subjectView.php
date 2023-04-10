<?php

/**
 * @copyright Copyrights (C) 2023 Arthur Gros All rights reserved.
 * @author Arthur Gros
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


$this->includeJS('board/exam/subjectView');

?>

<section id="subject-actions">
	<button class="text primary">
		<a href="<?=$this->url('/board/my-exams')?>">&lt; Retour</a>
	</button>
</section>

<section id="subject-view"></section>