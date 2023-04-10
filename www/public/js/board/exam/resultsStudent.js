/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

import { GET } from '../../ajax';
import { createToast, page } from '../../script';
import { renderSubject } from './renderSubject';

const examId = +page.url.substring(page.url.lastIndexOf('/')+1);

const $subjectWrapper = document.getElementById('subject-wrapper');

async function loadStudentSubject() {
    try
    {
		/** @type {import('./renderSubject').Subject} */
        const data = await GET(`/api/exams/byId/${examId}/results`);

		data.paper = {
			answers: data.answers,
			finalMark: 0
		};

        data.paper.finalMark = data.answers.reduce((prev, curr) => prev + curr.points, 0);

		delete data.answers;

		console.log(data);
        
        const $subject = renderSubject(data, true);

	    $subjectWrapper.appendChild($subject);
    }
    catch(e) {
		console.error(e);
		createToast('Une erreur est survenue lors de la récupération de la copie', false, 2000);
	}
}


if(!isNaN(examId)) {
	loadStudentSubject();
}