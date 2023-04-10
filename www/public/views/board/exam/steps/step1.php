<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');

?>

<h2>Génération des sujets</h2>

<article id="subject-chapters-selection">
	<section id="chapters-selection-wrapper">
		<h2 class="course-name"><?=$this->data['course']['name']?></h2>
		
		<h3>Nom de l'examen</h3>
		<input type="text" id="ipt-exam-name">

		<h3>Type d'examen</h3>
		<div id="exam-type">
			<label for="ipt-exam-type-1">
				<input type="radio" name="ipt-exam-type" id="ipt-exam-type-1" value="<?=ExamType::CC?>">
				Contrôle continu
			</label>
			<label for="ipt-exam-type-2">
				<input type="radio" name="ipt-exam-type" id="ipt-exam-type-2" value="<?=ExamType::CI?>">
				Contrôle intermédiaire
			</label>
			<label for="ipt-exam-type-3">
				<input type="radio" name="ipt-exam-type" id="ipt-exam-type-3" value="<?=ExamType::CF?>">
				Contrôle final
			</label>
		</div>
		

		<h3>Sélection des chapitres</h3>
		<ul>
<?php foreach($this->data['chapters'] as $i => $chapter) { ?>
			<li
				data-i="<?=$i?>"
				data-id="<?=$chapter['id']?>"
				data-max-questions="<?=$chapter['questionsCount']?>"
				data-text-count="<?=$chapter['questionsTextCount']?>"
				data-mcq-count="<?=$chapter['questionsMcqCount']?>">
				<?=$chapter['label']?>
			</li>
<?php } ?>
		</ul>
		
		<h3>Nombre de questions</h3>
		<div id="question-count">
			<input type="text" value="0" oninput="this.value = this.value.replace(/[^\d]/, '')" id="ipt-question-count"/>
			<span>/ <span id="max-questions-count">0</span></span>
		</div>
		
		<h3>Type de question</h3>
		<div id="question-type">
			<label>Texte</label>
			<div class="input-middle-range">
				<div class="range-delimiters"></div>
				<input type="range" min="0" max="100" step="1" id="ipt-question-type-perc"/>
			</div>
			<label>QCM</label>
			<span id="question-perc">0%</span>
		</div>

		<button id="btn-generate" class="primary nohref" disabled>Générer</button>
	</section>
	<section id="subjects-wrapper"></section>
</article>