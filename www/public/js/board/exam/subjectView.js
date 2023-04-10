/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */


import { renderSubject } from './renderSubject';
import { GET } from '../../ajax';
import { createToast, page } from '../../script';

const examId = +page.url.substring(page.url.lastIndexOf('/')+1);

const $subjectActions = document.getElementById('subject-actions');
const $subjectView = document.getElementById('subject-view');


if(!isNaN(examId)) {
	GET('/api/exams/byId/' + examId)
		.then(renderSubject)
		.then($subject => {
			$subjectView.appendChild($subject);
		})
		.catch(e => {
			console.error(e);
			createToast('Une erreur est survenue', false, 2000);
		});
}