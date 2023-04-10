<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');

?>


<footer id="main-footer">
    <ul>
        <li>
            <ul>
                <li>
                    <div class="app-logo">
                        <a href="<?=$this->url('/')?>"></a>
                    </div>
                </li>
            </ul>
        </li>
        <li>
            <ul>
                <li>Chartes</li>
                <li><a href="<?=$this->url('/about')?>">À propos</a></li>
                <li><a href="<?=$this->url('/terms')?>">Conditions d'utilisation</a></li>
                <li><a href="<?=$this->url('/privacy')?>">Politique de confidentialité</a></li>
                <li><a href="<?=$this->url('/contact-us')?>">Nous contacter</a></li>
            </ul>
        </li>
<?php if(!$this->auth()->isAuthenticated()) { ?>
        <li>
            <ul>
                <li>Établissements</li>
                <li><a href="<?=$this->url('/university/register')?>">S'enregistrer</a></li>
            </ul>
        </li>
        <li>
            <ul>
                <li>Compte</li>
                <li><a href="<?=$this->url('/login')?>">Se connecter</a></li>
                <li><a href="<?=$this->url('/user/forgot-password')?>">Mot de passe oublié</a></li>
            </ul>
        </li>
<?php }else{ ?>
        <li>
            <ul>
                <li>Mon compte</li>
                <li><a href="<?=$this->url('/user/reset-password')?>">Modifier votre mot de passe</a></li>
                <li><a href="<?=$this->url('/logout')?>">Se déconnecter</a></li>
            </ul>
        </li>
<?php }?>

    </ul>
    <p id="footer-copyrights">&copy; <?=$this->getAppName()?> <?=date("Y")?> - Tous droits réservés</p>
</footer>