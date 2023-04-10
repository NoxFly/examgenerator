<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');

?>

<head>
    <meta charset="UTF-8">

    <base href="<?php echo $this->req->baseUrl ?>">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" type="image/svg" href="asset/img/logo_app.svg"/>

    <?php
    
    $this->includeCSS("style");
    
    ?>

<?php if(isset($_SESSION['token'])) { ?>
    <script>
        const SESS_TOKEN = "<?=$_SESSION['token']?>";
    </script>
<?php } ?>
    <script src="static/libs/chart.umd.js"></script>
    <title><?php echo $this->getTitle(); ?></title>
</head>