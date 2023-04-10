<?php

/**
 * @copyright Copyrights (C) 2023 Jean-Charles Armbruster All rights reserved.
 * @author Jean-Charles Armbruster
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


function isStudent() {
	return bitwiseAND($_SESSION['privileges'], UserType::STUDENT);
}

function isTeacher() {
	return bitwiseAND($_SESSION['privileges'], UserType::TEACHER);
}

$this->includeJS('board/course/courseDetails');

if(isTeacher()) {
	$this->includeJS('board/course/TeacherCourse');
}

?>

<h1><?=$this->data["courseName"];?></h1>

<?php if(isStudent()) { ?>
	<?php if(isset($this->data['examId'])) { ?>
		<p class="center exam-available">
			<span>Un examen est disponible</span>
			<button class="primary">
				<a href="<?=$this->url('/board/exam/p/'.$this->data['examId'])?>">Le passer</a>
			</button>
		</p>
	<?php } ?>
<?php } // detail/summary ?>

<?php if(isTeacher()) { ?>
<div class="actions">
	<button class="nohref success btn-create-chapter">Nouveau chapitre</button>
	<button class="primary btn-create-chapter">
		<a href="<?=$this->url('/board/exam/new?course=' . $this->req->params['id'])?>" target="_blank">Nouvel examen</a>
	</button>
</div>
<?php } ?>



<div id="tab-nav">
	<ul>
		<li class="selected" data-i="0">Chapitres</li>
		<li data-i="1">Participants</li>
		<li data-i="2">Enseignant référent</li>
	</ul>
</div>

<div id="tab-content">
	<div class="selected" data-i="0">
		<article id="chapters-list">
<?php foreach($this->data["chapters"] as $chapters) { ?>
			<details id="chapter-<?=$chapters["id"]?>" data-id=<?=$chapters["id"]?>>
				<summary>
					<h2>
						<span><?=$chapters["label"]?></span>
<?php if(isTeacher()) { ?>
						<button class="btn-edit text nohref" data-id=<?=$chapters["id"]?>></button>
						<button class="btn-delete nohref text" data-id=<?=$chapters["id"]?>></button>
<?php } ?>
					</h2>
				</summary>

<?php if(isTeacher()) { ?>
				<table>
					<thead>
						<tr>
							<th class="q-num">N°</th>
							<th class="q-type">type</th>
							<th class="q-state">énoncé</th>
							<th class="q-props">propositions</th>
							<th class="q-ans">réponses</th>
							<th class="q-creation">date de création</th>
							<th class="q-actions"></th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
				<div class="actions">
					<button class="nohref primary btn-create-question" data-id=<?=$chapters["id"]?>>+ Créer</button>
				</div>
<?php } ?>
			</details>
<?php } ?>
		</article>
	</div>
	<div data-i="1">
		<h2>Participants :</h2>

		<ul>
<?php foreach($this->data['participants'] as $participant) { ?>
			<li><?=$participant['firstname']?> <?=$participant['lastname']?></li>
<?php } ?>
		</ul>
	</div>
	<div data-i="2">
		<h2>Enseignant référent :</h2>
		<p>
			<?=$this->data['referent']['firstname']?> <?=$this->data['referent']['lastname']?>
		</p>
	</div>
</div>

<?php if(isTeacher()) { ?>

<section id="delete-chapter" class="popup delete-popup hidden">
	<div></div>
	<article>
		<p class="center">
			Êtes-vous sûr de vouloir supprimer ce chapitre ?<br>
			<b>Tout ce qui est dépendant sera également supprimé définitivement.</b>
		</p>
		<div class="actions">
			<button class="medium nohref btn-cancel">Annuler</button>
			<button class="danger nohref btn-confirm">Supprimer</button>
		</div>
	</article>
</section>

<section id="delete-question" class="popup delete-popup hidden">
	<div></div>
	<article>
		<p class="center">
			Êtes-vous sûr de vouloir supprimer cette question ?<br>
			<b>Tout ce qui est dépendant sera également supprimé définitivement.</b>
		</p>
		<div class="actions">
			<button class="medium nohref btn-cancel">Annuler</button>
			<button class="danger nohref btn-confirm">Supprimer</button>
		</div>
	</article>
</section>

<section class="popup create-question-popup hidden">
	<div></div>
	<article>
		<form id="question-form">
			<div class="select" id="question-type">
				Type de question
				<opt data-value="<?=QuestionType::TEXT?>">TEXTE</opt>
				<opt data-value="<?=QuestionType::UNIQUE?>">CHOIX UNIQUE</opt>
				<opt data-value="<?=QuestionType::MULTIPLE?>">CHOIX MULTIPLE</opt>
			</div>
			<div class="sections">
				<div class="question-state">
					<label for="question-state">Énoncé</label>
					<textarea id="question-state"></textarea>
				</div>
				<?php /* QCM ONLY */ ?>
				<div class="question-propositions">
					<label>Propositions</label>
					<div class="inner"></div>
					<button class="btn-add-answer primary nohref">+ Ajouter</button>
				</div>
				<?php /* TEXT ONLY */ ?>
				<div class="question-answer">
					<label>Réponse</label>
					<textarea id="question-answer"></textarea>
				</div>
			</div>
		</form>
		<div class="actions">
			<button type="button" class="medium nohref btn-cancel">Annuler</button>
			<button type="submit" class="success nohref btn-confirm">Confirmer</button>
		</div>
	</article>
</section>

<section class="popup create-chapter-popup hidden">
	<div></div>
	<article>
		<form id="chapter-form">
			<div>
				<input type="text" id="chapterName" name="chapterName" placeholder="Nom du chapitre" required></input>
			</div>
			<div class="actions">
				<button type="button" class="medium nohref btn-cancel">Annuler</button>
				<button type="submit" id="btnConfirmChapter" class="success nohref btn-confirm">Confirmer</button>
			</div>
		</form>
	</article>
</section>

<?php } ?>