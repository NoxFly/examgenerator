<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');

?>

<section class="results-view">
	<table>
		<thead>
			<tr>
				<th>Année</th>
			</tr>
		</thead>
		<tbody>
<?php foreach($this->data['years'] as $year) { ?>
			<tr data-year="<?=$year?>">
				<td><?=$year?> - <?=$year + 1?></td>
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
		<button class="btn-new-item primary stroke">Nouveau</button>
	</div>
</section>


<section class="popup year-creation-popup hidden">
	<div></div>
	<article>
		<div class="actions">
			<button class="primary text nohref btn-cancel">&lt; Annuler</button>
			<button class="success nohref btn-confirm">Confirmer</button>
		</div>
		<div class="select" id="slct-year">
			Sélectionner une année
<?php for($y = $_SESSION['year']; $y > 1975; $y--) { ?>
	<?php if(!in_array($y, $this->data['years'])) { ?>
			<opt><?=$y?></opt>
	<?php } ?>
<?php } ?>
		</div>
	</article>
</section>

<?php $this->includeJS('board/admin/years'); ?>