<?php

/**
 * @copyright Copyrights (C) 2023 Arthur Gros All rights reserved.
 * @author Arthur Gros
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');

function getTypeName($type)
{
    switch ($type) {
        case ExamType::CC:
            return "CC";//"Contrôle continu";
        case ExamType::CI:
            return "CI";//"Contrôle intermediaire";
        case ExamType::CF:
            return "CF";//"Contrôle final";
        default:
            return "Inconnu";
    }
}

function getStateName($state)
{
    switch ($state) {
        case ExamState::COMING:
            return "à venir";
        case ExamState::PENDING:
            return "en cours";
        case ExamState::DONE:
            return "terminé";
        case ExamState::REVISED:
            return "corrigé";
        default:
            return "autre";
    }
}

function getBoutonStateName($state)
{
    switch ($state) {
        case ExamState::COMING:
            return "";
        case ExamState::PENDING:
            return "Passer l'examen";
        case ExamState::DONE:
            return "";
        case ExamState::REVISED:
            return "Voir la correction";
        default:
            return "";
    }
}

function ajouterVueExamen($site, $exam)
{
    echo '<button class="primary">'
    . '<a href="' . $site->url('/board/exam/v/' . $exam['id']). '">Voir</a>'
    . '</button>';
}

function printExamActions($site, $state, $exam) {
    switch($state) {
        case ExamState::DONE:
            if(bitwiseAND(UserType::TEACHER, $_SESSION['privileges'])) {
                echo  '<button class="primary">'
                    . '<a href="' . $site->url('/board/exam/m/') . $exam['id'] . '">Corriger</a>'
                    . '</button>';
            }
            break;

        case ExamState::REVISED:
            echo  '<button class="primary">'
                . '<a href="' . $site->url('/board/exam/r/') . $exam['id'] . '">Voir</a>'
                . '</button>';
            break;

        case ExamState::PENDING:
            if(bitwiseAND(UserType::STUDENT, $_SESSION['privileges'])) {
                echo  '<button class="primary">'
                    . '<a href="' . $site->url('/board/exam/p/') . $exam['id'] . '">Passer</a>'
                    . '</button>';
            }
            else if (bitwiseAND(UserType::TEACHER, $_SESSION['privileges']))
            {
                ajouterVueExamen($site, $exam);
            }
            break;
        case ExamState::COMING:
            if (bitwiseAND(UserType::TEACHER, $_SESSION['privileges']))
            {
                ajouterVueExamen($site, $exam);
                echo '<button class="nohref danger exam-delete">'
                . 'Supprimer'
                . '</button>';
            }
            break;
    }
}


?>

<h1>Mes examens</h1>

<?php if(bitwiseAND(UserType::TEACHER, $_SESSION['privileges'])) { ?>
<div class="actions">
    <button class="btn-create success">
        <a href="<?=$this->url('/board/exam/new')?>">+ Créer
        </a>
    </button>
</div>
<?php } ?>

<article>
<?php foreach ($this->data as $key => $states) { ?>
    <details>
        <summary>
            <h2><?=getStateName($key) ?></h2>
        </summary>

<?php if(count($states) === 0) { ?>
        <p class="center">Aucun examen <?=getStateName($key)?></p>
<?php } else { ?>

        <section>
            <table>
                <thead>
                    <tr>
                        <th>Cours</th>
                        <th>Nom</th>
                        <th>Type</th>
                        <th>Date début</th>
                        <th>Date fin</th>
                    </tr>
                </thead>
                <tbody>
<?php foreach ($states as $exam) { ?>
                        <tr data-id=<?=$exam['id']?>>
                            <td class="exam-course-name">
                                <div>
                                    <a href="<?=$this->url('/board/course/' . $exam['courseId']) ?>" target="_blank"><?=$exam['courseName']?></a>
                                </div>
                            </td>
                            <td class="exam-name"><?=$exam['name'] /* mettre liens vers l'exam */?></td>
                            <td class="exam-type"><?=getTypeName($exam['type']) /* faut changer par le nom */ ?></td>
                            <td class="exam-date-start"><?=$exam['dateStart']?></td>
                            <td class="exam-date-end"><?=$exam['dateEnd']?></td>
                            <td class="exam-action">
                                <?php printExamActions($this, $key, $exam); ?>
                            </td>
                        </tr>
<?php } ?>
                </tbody>
            </table>
        </section>
<?php } ?>
    </details>
<?php } ?>
</article>

<?php $this->includeJS('board/exam/my-exams'); ?>