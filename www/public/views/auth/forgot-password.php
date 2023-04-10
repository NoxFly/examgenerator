<?php

/**
 * @copyright Copyrights (C) 2023 Arthur Gros All rights reserved.
 * @author Arthur Gros
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');

?>

<h1>Mot de passe oublié</h1>

<form>
    <p class="feedback-message"></p>
    <div>
        <label>Mail utilisateur</label>
        <input type="email" id="ipt-forgot-mail" name ="mail" placeholder="jean.dupont@univ.fr" required> 
    </div>

    <button type="submit" class="block primary">Valider</button>
</form>

<?php $this->includeJS('password-forgot'); ?>
