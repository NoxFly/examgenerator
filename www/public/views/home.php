<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');

?>

<h1>ExamGenerator</h1>

<?php if($this->auth()->isAuthenticated()) {

$fullname = '';

if($_SESSION['privileges'] === UserType::ADMIN) {
	$fullname = 'Administrateur de ' . $_SESSION['user']->universityName;
}
else {
	$fullname = $_SESSION['user']->firstname . ' ' . $_SESSION['user']->lastname;
}
?>


<p class="center">Bienvenue <?=$fullname?></p>

<?php } else { ?>

<p class="center">
	Vous êtes une université et vous souhaitez utiliser notre solution ?
</p>
<button class="primary">
	<a href="<?=$this->url('/university/register')?>">S'enregistrer</a>
</button>

<?php } ?>