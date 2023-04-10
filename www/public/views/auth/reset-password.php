<?php

/**
 * @copyright Copyrights (C) 2023 Arthur Gros All rights reserved.
 * @author Arthur Gros
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');

?>

<h1>Modifications du mot de passe</h1>

<form>
    <p class="feedback-message"></p>

    <div>
        <label>Ancien mot de passe</label>
        <input type="password" name="oldpasswd" id="ipt-reset-old" required>
    </div>

    <div>
        <label>Nouveau mot de passe</label>
        <input type="password" name="newpasswd1" id="ipt-reset-new1" required>
    </div>

    <div>
        <label>Confirmer mot de passe</label>
        <input type="password" name="newpasswd2" id="ipt-reset-new2" required>
    </div>

    <button type="submit" class="block primary">Valider</button>
</form>

<?php $this->includeJS('password'); ?>
