 /**
 * @copyright Copyrights (C) 2023 Arthur Gros All rights reserved.
 * @author Arthur Gros
 * @since 2023
 * @package uha.archi_web
 */

import { renderSubject } from './renderSubject';
import { GET, PUT } from '../../ajax';
import { page, createToast } from '../../script';


const examId = +page.url.substring(page.url.lastIndexOf('/') + 1);

GET('/api/exams/byId/' + examId)
    .then(res => {console.log(res); return res;})
	.then(res => renderSubject(res, false))
	.then($subject => document.body.querySelector('#subject-wrapper')?.appendChild($subject))
	.catch(e => {
		console.error(e);
		createToast('Une erreur est survenue lors de la récupération de l\'examen', false, 2000);
	});


async function sendSubject() {
	try {
		const answers = {};

        const $textareas = document.getElementsByClassName("prop-text");

        for (let div of $textareas)
        {
            const $textarea = div.firstChild;
            const questionId = ($textarea.id).split('-')[1];
            const text = $textarea.value.trim();

            answers[questionId] = text;
        }

        const $qcmareas = document.getElementsByClassName("prop-mcq");

        for (const $question of $qcmareas)
        {
            const children = $question.children;

            const $form = $question.closest('.question-mcq');
            
            const questionId = $form?.dataset.questionId?? 0;
            
            if($question.classList.contains('mcq-type-radio')) {
                answers[questionId] = null;

                for(let i=0; i < children.length; i++) {
                    const choice = children[i].firstChild;

                    if(choice.checked) {
                        answers[questionId] = i;
                        break;
                    }
                }
            }
            else if($question.classList.contains('mcq-type-checkbox')) {
                answers[questionId] = [];

                for (let i = 0; i < children.length; i++) 
                {
                    const choice = children[i].firstChild;

                    if (choice.checked)
                    {
                        answers[questionId].push(i);
                    }
                }
            }
        }

		document.getElementById('hover-popup').classList.remove('hidden');

		await PUT(`/api/exams/${examId}/student/answer`, answers);

		createToast('Sujet envoyé. Redirection dans 2 secondes...', true, 2000);

		setTimeout(() => {
			window.location.href = page.baseUrl + '/board/my-exams';
		}, 2000);
	}
	catch(e) {
		console.error(e);
		createToast('Une erreur est survenue', false, 2000);
	}
    finally {
		document.getElementById('hover-popup').classList.add('hidden');
    }
}

const button = document.getElementById("submit-exam");

button?.addEventListener("click", sendSubject);