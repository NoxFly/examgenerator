<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');

if(bitwiseAND(UserType::STUDENT, $_SESSION['privileges'])) {
$this->includeJS('board/exam/resultsStudent');
}
else if(bitwiseAND(UserType::TEACHER, $_SESSION['privileges'])) {
$this->includeJS('board/exam/resultsTeacher');
}

?>
<style>
    #canvas-holder{
        text-align: center;
    }
    #chart{
        margin-left: auto;
        margin-right: auto;
        display: inline-block;
    }
</style>

<h1>Résultats d'un examen</h1>

<div id="canvas-holder">
    <canvas id="chart"></canvas>
</div>

<div id="subject-wrapper"></div>