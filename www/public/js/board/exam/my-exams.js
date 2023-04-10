/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

import { DELETE } from "../../ajax";
import {createToast} from "../../script"

const boutons=document.getElementsByClassName('exam-delete');


for (let b of boutons)
{
    b.addEventListener("click", function(){deleteExam(b)});
}

async function deleteExam($button)
{
  try {
    const $tr = $button.closest('tr');
    const examId = $tr.dataset.id;

    await DELETE('/api/exams/' + examId);

    $tr.remove();

    createToast('Examen supprimé', true, 2000);
  }
  catch(e) {
    console.error(e);
    createToast('Une erreur est survenue', false, 2000);
  }
}