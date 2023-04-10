<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');

$pageClass = str_replace('/', '-', preg_replace('/\/:\w+/', '', $this->req->route));

?>

<!DOCTYPE html>
<html lang="en">
    <?php $this->requireComponent("head.php"); ?>

    <body>
        <?php $this->requireComponent("nav.php"); ?>

        <main id="content" class="page<?=$pageClass?>">
            <?php echo $this->getContent(); ?>
        </main>

        <?php $this->includeJS('script'); ?>
        <?php $this->includeJS('form'); ?>

        <?php $this->requireComponent("footer.php"); ?>
    </body>
</html>