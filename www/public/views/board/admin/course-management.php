<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');

?>

<section class="filter-bar">
	<div class="select multiple" id="slct-year">
		Année scolaire
		<opt data-value="*" data-selected>Toutes</opt>
<?php foreach($this->data['fields']['years'] as $year) { ?>
		<opt><?=$year?></opt>
<?php } ?>
	</div>
	<div class="select multiple" id="slct-cursus">
		Cursus
		<opt data-value="*" data-selected>Tous</opt>
<?php foreach($this->data['fields']['cursus'] as $cursus) { ?>
		<opt data-value="<?=$cursus['id']?>"><?=$cursus['name']?></opt>
<?php } ?>
	</div>
	<div>
		<input type="checkbox" id="ipt-without-referent">
		<label for="ipt-without-referent">Sans référent</label>
	</div>
	<div>
		<input type="checkbox" id="ipt-this-year">
		<label for="ipt-this-year">Année courante</label>
	</div>

	<div class="search-bar">
		<input type="text" placeholder="Rechercher..."/>
	</div>
</section>

<section class="results-view">
	<table>
		<thead>
			<tr>
				<th class="select-box">
					<div>
						<input type="checkbox" name="row" value="*"/>
					</div>
				</th>
				<th>Nom</th>
				<th>Référent</th>
				<th>Année actuelle</th>
				<th>Nombre de cursus</th>
			</tr>
		</thead>
		<tbody data-year="<?=$this->data['fields']['years'][0]?>">
<?php foreach($this->data['courses'] as $course) { ?>
			<tr data-id="<?=$course['id']?>">
				<td class="select-box">
					<div>
						<input type="checkbox" name="row" value="<?=$course['id']?>"/>
					</div>
				</td>
				<td class="course-name"><?=$course['name']?></td>
				<td class="course-referent"></td>
				<td class="course-current-year"></td>
				<td class="course-cursus-count"></td>
			</tr>
<?php } ?>
		</tbody>
	</table>
</section>

<section class="actions-panel">
	<div>
		<?php $this->includeComponent('board/admin-frame-pagination.php'); ?>
	</div>
	<div>
		<button class="btn-edit-item primary stroke" disabled>Modifier</button>
		<button class="btn-new-item primary stroke">Nouveau</button>
		<button class="btn-delete-item danger stroke" disabled></button>
	</div>
</section>


<section class="popup creation-popup course-creation-popup hidden">
	<div></div>
	<article>
		<div class="actions">
			<button class="primary text nohref btn-cancel">&lt; Annuler</button>
			<button class="success nohref btn-confirm">Confirmer</button>
		</div>
		<form>
			<div>
				<label for="ipt-crt-name">Nom :</label>
				<input type="text" id="ipt-crt-name"/>
			</div>
			<div>
				<label for="slct-referent">Enseignant référent :</label>
				<div class="select" id="slct-referent">
					Sélectionner un référent
					<opt data-value="none">Aucun</opt>
<?php foreach($this->data['fields']['teachers'] as $teacher) { ?>
					<opt data-value="<?=$teacher['teacherId']?>"><?=$teacher['userMail']?></opt>
<?php } ?>
				</div>
			</div>
		</form>
	</article>
</section>

<section class="popup delete-popup course-deletion-popup hidden">
	<div></div>
	<article>
		<p class="center">
			Êtes-vous sûr de vouloir supprimer cette matière ?<br>
			<b>Cette action est irresversible et tout ce qui est dépendant sera supprimé.</b>
		</p>
		<div class="actions">
			<button class="medium nohref btn-cancel">Annuler</button>
			<button class="danger nohref btn-confirm">Confirmer</button>
		</div>
	</article>
</section>

<?php $this->includeJS('board/admin/courseList'); ?>