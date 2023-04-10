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
		<opt data-value="<?=$year?>"><?=$year?> - <?=$year+1?></opt>
<?php } ?>
	</div>
	<div class="select multiple" id="slct-cursus">
		Cursus
		<opt data-value="*" data-selected>Tous</opt>
<?php foreach($this->data['fields']['cursus'] as $cursus) { ?>
		<opt data-value="<?=$cursus['id']?>"><?=$cursus['name']?></opt>
<?php } ?>
	</div>
	<div class="select multiple" id="slct-level">
		Niveau
		<opt data-value="*" data-selected>Tous</opt>
<?php foreach($this->data['fields']['levels'] as $cursusId => $cursusLevels) { ?>
	<?php foreach($cursusLevels as $level) { ?>
		<opt data-value="<?=$level['id']?>" data-cursus="<?=$cursusId?>"><?=$level['name']?></opt>
	<?php } ?>
<?php } ?>
	</div>
	
	<div class="switch-1" id="switch-user-type">
		<label class="<?=($this->req->query['role']==='teacher')?'active':''?>">
			<input type="radio" name="user-type" value="teacher"/>
			enseignant
		</label>
		<label class="<?=($this->req->query['role']==='student')?'active':''?>">
			<input type="radio" name="user-type" value="student"/>
			étudiant
		</label>
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
				<th>Numéro</th>
				<th>Prénom</th>
				<th>Nom</th>
				<th>Mail</th>
			</tr>
		</thead>
		<tbody>
<?php foreach($this->data['users'] as $user) { ?>
			<tr>
				<td class="select-box">
					<div>
						<input type="checkbox" name="row" value="<?=$user['userId']?>"/>
					</div>
				</td>
				<td><?=$user['userUUID']?></td>
				<td><?=$user['firstname']?></td>
				<td><?=$user['lastname']?></td>
				<td><?=$user['userMail']?></td>
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


<section class="popup delete-popup hidden">
	<div></div>
	<article>
		<p class="center">
			Êtes-vous sûr de vouloir supprimer cet utilisateur ?<br>
			<b>Cette action est irresversible et tout ce qui est dépendant sera supprimé.</b>
		</p>
		<div class="actions">
			<button class="medium nohref btn-cancel">Annuler</button>
			<button class="danger nohref btn-confirm">Supprimer</button>
		</div>
	</article>
</section>

<section class="popup creation-popup hidden">
	<div></div>
	<article>
		<div class="actions">
			<button class="text primary nohref btn-cancel">&lt; Annuler</button>
			<button class="success nohref btn-confirm">Confirmer</button>
		</div>
		<form>
			<div>
				<div>
					<label for="create-user-lastname">Nom :</label>
					<input type="text" id="create-user-lastname" name="crt-usr-lst"/>
				</div>
				<div>
					<label for="create-user-firstname">Prénom :</label>
					<input type="text" id="create-user-firstname" name="crt-usr-frst"/>
				</div>
				<div>
					<label for="create-user-uuid">Numéro :</label>
					<input type="text" id="create-user-uuid" name="crt-usr-uuid"/>
				</div>
			</div>
			<div>
				<div>
					<label for="create-user-mail">Mail :</label>
					<input type="email" id="create-user-mail" name="crt-usr-mail"/>
				</div>
				<div>
					<label for="create-user-pass">Mot de passe :</label>
					<input type="text" id="create-user-pass" name="crt-usr-pass"/>
				</div>
			</div>
			<div>
				<div>
					<label for="create-user-role-student">Etudiant :</label>
					<input type="checkbox" id="create-user-role-student" name="crt-usr-role-st" value="<?=UserType::STUDENT?>" data-value="student"/>
				</div>
				<div>
					<label for="create-user-role-teacher">Enseignant :</label>
					<input type="checkbox" id="create-user-role-teacher" name="crt-usr-role-te" value="<?=UserType::TEACHER?>" data-value="teacher"/>
				</div>
			</div>
			<div class="student-only hidden">
				<div class="select" id="crt-slct-year">
					Année scolaire
<?php foreach($this->data['fields']['years'] as $year) { ?>
					<opt data-value="<?=$year?>"><?=$year?> - <?=$year+1?></opt>
<?php } ?>
				</div>
				<div class="select" id="crt-slct-cursus">
					Cursus
<?php foreach($this->data['fields']['cursus'] as $cursus) { ?>
					<opt data-value="<?=$cursus['id']?>"><?=$cursus['name']?></opt>
<?php } ?>
				</div>
				<div class="select" id="crt-slct-level">
					Niveau
<?php foreach($this->data['fields']['levels'] as $cursusId => $cursusLevels) { ?>
	<?php foreach($cursusLevels as $level) { ?>
					<opt data-value="<?=$level['id']?>" data-cursus="<?=$cursusId?>"><?=$level['name']?></opt>
	<?php } ?>
<?php } ?>
				</div>
			</div>
		</form>
	</article>
</section>



<?php $this->includeJS('board/admin/users'); ?>
