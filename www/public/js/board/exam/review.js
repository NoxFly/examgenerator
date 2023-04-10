/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

import { GET, POST } from '../../ajax';
import { createToast, page } from '../../script';
import { renderSubject } from './renderSubject';

/** @typedef {import('./renderSubject').Subject} Subject */
/** @typedef {import('./renderSubject').Student} Student */

const examId = +page.url.substring(page.url.lastIndexOf('/')+1);


const $subjectWrapper = document.getElementById('subject-wrapper');
const $saveBtn = document.getElementById('btn-save');
const $nextBtn = document.getElementById('btn-next');
const $opPopup = document.getElementById('op-popup');

let answerIdx = -1;
/** @type {Subject} */
let subject = {};
/** @type {{ [uuid: string]: Student }} */
let answers = {};
let uuids = [];
let modifications = {};


async function loadStudentSubject(studentUUID) {
	$subjectWrapper.innerHTML = '';


	/** @type {Subject} */
	const studentSubject = {
		overview: subject.overview,
		chapters: subject.chapters,
		questions: subject.questions,
		paper: answers[studentUUID]
	};

	const $subject = renderSubject(studentSubject, true, true);

	$subject.querySelectorAll('.ipt-teacher-review').forEach($ipt => {
		$ipt.addEventListener('input', updateSubjectReview);
	});

	$subjectWrapper.appendChild($subject);

	if(answers[studentUUID].finalMark === null) {
		$nextBtn.setAttribute('disabled', true);
	}
	else {
		$nextBtn.removeAttribute('disabled');
	}
}

function loadNextSubject() {
	answerIdx++;

	if(answerIdx < uuids.length) {
		loadStudentSubject(uuids[answerIdx]);
	}
	else {
		createToast('Toutes les copies ont été corrigées', true, 10000);

		answerIdx = 0;
		loadStudentSubject(uuids[answerIdx]);

		$saveBtn.setAttribute('disabled', true);
	}
}

async function loadSubjects() {
	try {
		const data = await GET(`/api/exams/byId/${examId}/results`);

		if(Object.keys(data.answers).length === 0) {
			$subjectWrapper.innerHTML = '<p class="center">Aucune réponse n\'a été enregistrée pour cet examen.</p>';
			return;
		}


		answers = data.answers;
		uuids = Object.keys(answers);
		modifications = {};

		uuids = uuids.sort((a, b) => {
			const am = answers[a].finalMark;
			const bm = answers[b].finalMark;
			
			if(am === null)
				return -1;
			
			if(bm === null)
				return 1;
			
			if(am < bm)
				return -1;

			return 1;
		});

		if(uuids.every(id => answers[id].finalMark !== null)) {
			createToast('Toutes les copies ont été corrigées', true, 10000);
		}

		delete data.answers;

		subject = data;

		loadNextSubject();
	}
	catch(e) {
		console.error(e);
		createToast('Une erreur est survenue lors de la récupération des copies', false, 2000);
	}
}

function checkStudentModifications() {
	const modified = Object.keys(modifications).length > 0;

	if(modified) {
		$saveBtn.removeAttribute('disabled');
	}
	else {
		$saveBtn.setAttribute('disabled', true);
	}
}

function updateSubjectReview() {
	const student = answers[uuids[answerIdx]];
	const questionId = +this.closest('form')?.dataset.questionId;
	const question = student.answers.find(q => q.questionId === questionId);

	const hasChanged = this.parentElement.classList.contains('changed');

	if(hasChanged) {
		if(!$nextBtn.hasAttribute('disabled')) {
			$nextBtn.setAttribute('disabled', true);
		}
	}
	else if(answers[uuids[answerIdx]].finalMark !== null) {
		if($nextBtn.hasAttribute('disabled')) {
			$nextBtn.removeAttribute('disabled');
		}
	}

	let field = null;
	let value = null;

	if(!question) {
		return;
	}

	if(this.classList.contains('ipt-mark')) {
		value = +this.value;
		field = 'points';
	}
	else if(this.classList.contains('ipt-comment')) {
		value = this.value.trim();
		field = 'comment';
	}

	if(!field) {
		return;
	}

	question[field] = value;

	if(hasChanged) {
		if(!(questionId in modifications)) {
			modifications[questionId] = {};
		}

		modifications[questionId][field] = value;
	}
	else {
		delete modifications[questionId][field];

		if(Object.keys(modifications[questionId]).length === 0) {
			delete modifications[questionId];
		}
	}


	checkStudentModifications();
}


async function saveReview() {
	$opPopup.classList.remove('hidden');

	console.log(modifications);

	$saveBtn.setAttribute('disabled', true);

	try {
		const studentId = answers[uuids[answerIdx]].studentId;

		await POST(`/api/exams/${examId}/student/${studentId}/correct`, modifications);

		if($subjectWrapper.querySelectorAll('.mark.need-completion').length === 0) {
			$nextBtn.removeAttribute('disabled');
		}
		else {
			$nextBtn.setAttribute('disabled', true);
		}

		const student = answers[uuids[answerIdx]];

		for(const questionId in modifications) {
			const modif = modifications[questionId];
			const question = student.answers.find(q => q.questionId === +questionId);
			const $question = $subjectWrapper.querySelector(`[data-question-id="${questionId}"]`);

			if('points' in modif) {
				const $mark =  $question.querySelector('.mark.changed');
				$mark?.classList.remove('changed');

				if(question) {
					question.points = modif.points;
				}
			}

			if('comment' in modif) {
				const $comment = $question.querySelector('.comment.changed');
				$comment?.classList.remove('changed');

				if(question) {
					question.comment = modif.comment;
				}
			}
		}

		modifications = {};

		createToast('Modifications sauvegardées', true, 2000);
	}
	catch(e) {
		console.error(e);
		createToast('Une erreur est survenue', false, 2000);

		$saveBtn.removeAttribute('disabled');
	}
	finally {
		$opPopup.classList.add('hidden');
	}
}


$saveBtn?.addEventListener('click', saveReview);
$nextBtn?.addEventListener('click', loadNextSubject);


if(!isNaN(examId)) {
	loadSubjects();
}