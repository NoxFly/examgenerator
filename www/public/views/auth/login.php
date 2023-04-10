<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');

?>

<h1>Connexion</h1>

<form>
    <p class="feedback-message"></p>
    <div>
        <label>Identifiant</label>
        <input type="email" name="id" id="ipt-login-id" placeholder="jean.dupont@univ.fr"/>
    </div>
    <div>
        <label>Mot de passe</label>
        <input type="password" name="password" id="ipt-login-psswd" placeholder="************"/>
    </div>

    <button type="submit" class="block primary">Se connecter</button>
    <a href="<?=$this->url('/user/forgot-password')?>">Mot de passe oublié ?</a>
</form>

<?php $this->includeJS('login'); ?>