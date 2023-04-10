/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

import { page } from '../../../script';
import { PUT } from '../../../ajax';
import { createToast } from '../../../script';
import { common } from "../common";

const minTimeDistance = 30; // minutes
const currentYear = new Date().getFullYear();
const yearEndTime = new Date(currentYear, 6, 31, 0, 0, 0);
const tzoffset = (new Date()).getTimezoneOffset() * 60000; // offset in milliseconds

let $levels, $dateStart, $dateEnd, $confirmBtn;


export function load() {
	$levels = document.querySelector('.slct-levels');
	$dateStart = document.getElementById('date-start');
	$dateEnd = document.getElementById('date-end');
	$confirmBtn = document.getElementById('btn-create-exam');

	$dateStart?.addEventListener('change', dateStartChange);

	$confirmBtn?.addEventListener('click', confirmStep);

	$dateStart.min = getISODate(now()); 
	$dateStart.max = getISODate(yearEndTime);

	$dateEnd.min = getISODate(addMinutes(now(), minTimeDistance)); 
	$dateEnd.max = getISODate(addMinutes(yearEndTime, minTimeDistance));

	$dateStart.value = $dateStart.min;
	$dateEnd.value = $dateEnd.min;
}

export function unload() {
	$dateStart?.removeEventListener('change', dateStartChange);
	$confirmBtn?.removeEventListener('click', confirmStep);
}

function dateStartChange() {
	updateDateEnd();
}

function updateDateEnd() {
	const dateStart = new Date($dateStart.value);
	const dateEnd = new Date($dateEnd.value);

	const ttds = dateEnd - 0;
	const ttde = addMinutes(dateEnd, -minTimeDistance);

	if(ttde - ttds <= 0) {
		$dateEnd.min = getISODate(addMinutes(dateStart, minTimeDistance));
		$dateEnd.value = $dateEnd.min;
	}
}



function confirmStep() {
	const sDateStart = $dateStart.value;
	const sDateEnd = $dateEnd.value;
	const sLevels = $levels.dataset.value;

	if(sLevels.length === 0) {
		createToast('Veuillez sélectionner les niveaux qui auront l\'examen', false, 2000);
		return;
	}

	if(sDateStart.length === 0 || sDateEnd.length === 0) {
		return;
	}

	const levels = sLevels.split(';').filter(l => !!l).map(l => +l);
	let dateStart = new Date(sDateStart);
	let dateEnd = new Date(sDateEnd);

	if(dateStart.getTime() < Date.now()) {
		$dateStart.value = getISODate(new Date());
		updateDateEnd();

		dateStart = new Date($dateStart.value);
		dateEnd = new Date($dateEnd.value);
	}

	/* const ttds = dateStart - 0;
	const ttde = addMinutes(dateEnd, -minTimeDistance);

	if(ttds <= ttde) {
		createToast('La date de fin n\'est pas assez loin de la date de début.', false, 2000);
		return;
	} */

	common.dates = {
		start: dateStart - 0,
		end: dateEnd - 0
	};

	common.levels = levels;

	createExam();
}

/**
 * 
 * @param {Date} date 
 * @param {number} minutes 
 * @returns {Date}
 */
function addMinutes(date, minutes) {
    return new Date(date.getTime() + minutes * 60000);
}

/**
 * 
 * @param {Date} date 
 * @returns {string}
 */
function getISODate(date) {
    date = (new Date(date.getTime() - tzoffset));
	return date.toISOString().slice(0, -8); // yyyy-MM-ddThh:mm
}

function now() {
	return new Date();
}


async function createExam() {
	const $popup = document.body.querySelector('.create-popup');

	try {
		$popup?.classList.remove('hidden');

		const questions = {};

		for(const question of common.marks) {
			questions[question.id] = [question.points/* , negPoints */];
		}

		await PUT('/api/exams', {
			overview: {
				courseId: 	common.subject.overview.courseId,
				name: 		common.subject.overview.name,
				coeff: 		common.subject.overview.coeff,
				type: 		common.subject.overview.type,
				dateStart: 	common.dates.start / 1000,
				dateEnd: 	common.dates.end / 1000
			},
			questions,
			target: common.levels
		});

		createToast('Examen créé. Redirection dans 2 secondes...', true, 2000);

		setTimeout(() => {
			window.location.href = page.baseUrl + '/board/my-exams';
		}, 2000);
	}
	catch(e) {
		console.error(e);

		$popup?.classList.add('hidden');

		createToast('Une erreur est survenue', false, 2000);
	}
}