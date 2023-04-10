<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');

?>

<h1>Enregistrer votre université</h1>

<form>
    <p class="feedback-message"></p>
    <div>
        <label>Nom de l'université</label>
        <input type="text" name="university" id="ipt-reg-id" placeholder="Université de Paris"/>
    </div>
    <div>
        <label>Domaine de l'université</label>
        <input type="text" name="domain" id="ipt-reg-domain" placeholder="univ.fr" pattern="([\w-]+\.)+[\w-]{2,4}"/>
    </div>
    <div>
        <label>Mot de passe</label>
        <input type="password" name="password" id="ipt-reg-psswd" placeholder="************">
    </div>

    <button type="submit" class="block primary">S'enregistrer</button>
    <p>Déjà membre ? <a href="<?=$this->url('/login')?>">Se connecter</a></p>
</form>

<?php $this->includeJS('register'); ?>