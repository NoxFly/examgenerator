<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');



$navFile = 'guest';

if($this->auth()->isAuthenticated()) { // REGISTERED USER
    $navFile = ($_SESSION['privileges'] === UserType::ADMIN)
        ? 'admin'
        : (
            ($_SESSION['privileges'] === UserType::TEACHER)
                ? 'teacher'
                : 'student'
        );
}

$navFile .= 'Nav';

?>


<nav id="main-nav">
    <div class="app-logo"><a href="<?=$this->url('/')?>"></a></div>

    <?php $this->includeComponent("nav/$navFile.php"); ?>
</nav>