<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');

?>




<?php if(isset($this->data['details'])) { ?>

<section class="filter-bar">
	<a href="<?=$this->url('/board/admin/cursus')?>"></a>
	<h3>Cursus <?=$this->data['details']['name']?></h3>
</section>

<section class="results-view">
	<div class="view-split">
		<div class="level-list">
			<h4>Niveaux</h4>
			<table>
				<tbody>
<?php foreach($this->data['details']['levels'] as $level) { ?>
					<tr data-id="<?=$level['id']?>">
						<td><?=$level['name']?></td>
					</tr>
<?php } ?>
				</tbody>
			</table>
			<button class="medium stroke nohref create-level">+ Créer</button>
		</div>
		<div class="level-details">
			<p class="center">Sélectionnez un niveau pour voir ses détails.</p>
			<section class="course-list hidden"></section>
			<button class="medium stroke nohref add-course hidden">+ Ajouter une matière</button>
		</div>
	</div>
</section>

<section class="actions-panel">
	<div>
		<button class="danger stroke nohref delete-level hidden">Supprimer le cursus</button>
	</div>
	<div>
		<button class="danger stroke nohref delete-course hidden" disabled>Enlever la matière</button>
	</div>
</section>


<section class="popup delete-popup hidden">
	<div></div>
	<article>
		<p class="center">
			Êtes-vous sûr de vouloir supprimer <span></span> ?<br>
			<b>Cette action est irresversible et tout ce qui est dépendant sera supprimé.</b>
		</p>
		<div class="actions">
			<button class="medium nohref btn-cancel">Annuler</button>
			<button class="danger nohref btn-confirm">Confirmer</button>
		</div>
	</article>
</section>


<?php $this->includeJS('board/admin/cursusDetails'); ?>






<?php } else { // end of details - beginning of list ?>

<section class="filter-bar filter-right">
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
						<input type="checkbox" value="*">
					</div>
				</th>
				<th>Nom</th>
			</tr>
		</thead>
		<tbody>
<?php foreach($this->data['list'] as $cursus) { ?>
			<tr data-id="<?=$cursus['id']?>">
				<td class="select-box">
					<div>
						<input type="checkbox" value="<?=$cursus['id']?>">
					</div>
				</td>
				<td class="cursus-name"><?=$cursus['name']?></td>
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



<section class="popup cursus-creation-popup hidden">
	<div></div>
	<article>
		<div class="actions">
			<button class="primary nohref text btn-cancel">&lt; Annuler</button>
			<button class="success nohref btn-confirm">Confirmer</button>
		</div>
		<input type="text" placeholder="Nom" name="cursus-name"/>
	</article>
</section>

<section class="popup delete-popup hidden">
	<div></div>
	<article>
		<p class="center">
			Êtes-vous sûr de vouloir supprimer ce cursus ?<br>
			<b>Cette action est irresversible et tout ce qui est dépendant sera supprimé.</b>
		</p>
		<div class="actions">
			<button class="medium nohref btn-cancel">Annuler</button>
			<button class="danger nohref btn-confirm">Confirmer</button>
		</div>
	</article>
</section>




<?php $this->includeJS('board/admin/cursusList'); ?>

<?php } ?>