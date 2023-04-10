/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

import { DELETE, GET, POST, PUT } from '../../ajax';
import { createSelect } from '../../form';
import { createToast, page } from '../../script';



const $resultsView = document.querySelector('.results-view');
const $actionsPanel = document.querySelector('.actions-panel');


const levels = {};
const courses = {};

let selectedRows = {};

let currentLevel = null;
let action = null;
let $iptLvlCrt = null;
let alreadyFetchCourses = false;

const cursusId = +page.url.substring(page.url.lastIndexOf('/')+1);

const $levelsList = $resultsView?.querySelector('.level-list table');
const $levelsTbody = $levelsList?.querySelector('tbody');
const $coursesTbody = $resultsView?.querySelector('.course-list');
const $pEmptyLevel = $resultsView?.querySelector('.level-details > p');
const $createLevelBtn = $resultsView?.querySelector('.level-list .create-level');
const $addCourseBtn = $resultsView?.querySelector('.level-details .add-course');
const $delLevelBtn = $actionsPanel?.querySelector('.delete-level');
const $delCourseBtn = $actionsPanel?.querySelector('.delete-course');
const $deletePopup = document?.querySelector('.delete-popup');

$levelsTbody?.addEventListener('click', changeLevel, false);
$delLevelBtn?.addEventListener('click', openDeleteLevelPopup);
$delCourseBtn?.addEventListener('click', openDeleteCoursePopup);
$deletePopup?.querySelector('.btn-cancel')?.addEventListener('click', cancelDeletion);
$deletePopup?.querySelector('.btn-confirm')?.addEventListener('click', confirmDeletion);
$createLevelBtn?.addEventListener('click', showInputLevelCreation);
$addCourseBtn?.addEventListener('click', openCourseAddPopup);

async function changeLevel(e) {
	if(e.target.nodeName === 'TD') {
		/** @type {HTMLTableRowElement} */
		const $tr = e.target.closest('tr');

		$levelsTbody.querySelector('tr.selected')?.classList.remove('selected');

		$tr.classList.add('selected');

		$delLevelBtn?.classList.remove('hidden');
		$delCourseBtn?.classList.remove('hidden');

		$delCourseBtn?.setAttribute('disabled', true);
		$delLevelBtn?.removeAttribute('disabled');

		currentLevel = +$tr.dataset.id;

		try {
			await fetchLevel(currentLevel);

			updateCourseTable(currentLevel);
		}
		catch(e) {
			console.error(e);
			createToast('Une erreur est survenue lors du chargement du niveau', false, 2000);
		}
	}
}

async function fetchLevel(levelId) {
	if(!(levelId in levels)) {
		try {
			const level = await GET(`/api/cursus/${cursusId}/levels/${levelId}`);

			levels[levelId] = level;

			levels[levelId].courses = levels[levelId].courses.map(c => {
				courses[c.id] = c;
				return c.id;
			});
		}
		catch(e) {
			console.error(e);
			createToast('Une erreur est survenue lors du chargement du niveau', false, 2000);
		}
	}
}

function updateCourseTable(levelId) {
	if(!$coursesTbody) {
		return;
	}

	$coursesTbody.innerHTML = '';

	if(levels[levelId].courses.length === 0) {
		$coursesTbody?.classList.add('hidden');
		$addCourseBtn?.classList.add('hidden');

		const $btnAdd = document.createElement('button');
		$btnAdd.className = 'primary nohref btn-add-empty';
		$btnAdd.innerText = '+ Ajouter';

		$btnAdd.addEventListener('click', openCourseAddPopupEmptyLevel);

		$pEmptyLevel.innerHTML = 'Ce niveau ne contient aucun cours';

		$pEmptyLevel.insertAdjacentElement('beforeend', $btnAdd);

		$pEmptyLevel.classList.remove('hidden');
	}
	else {
		$addCourseBtn?.classList.remove('hidden');
		$coursesTbody?.classList.remove('hidden');
		$pEmptyLevel?.classList.add('hidden');

		for(const courseId of levels[levelId].courses) {
			addCourseRow(courseId);
		}
	}
}

function addCourseRow(courseId) {
	const $row = document.createElement('div');
	$row.classList.add('course-row');
	$row.dataset.id = courseId;

	const $slctBox = document.createElement('div');
	$slctBox.classList.add('course-select-box');
	
	const $ipt = document.createElement('input');
	$ipt.setAttribute('type', 'checkbox');
	$ipt.value = courseId;

	const $courseName = document.createElement('div');
	$courseName.classList.add('course-name');
	$courseName.innerHTML = `<a href="${page.baseUrl}/board/course/${courseId}" target="_blank">${courses[courseId].name}</a>`;

	$slctBox.appendChild($ipt);

	$row.appendChild($slctBox);
	$row.appendChild($courseName);

	$ipt.addEventListener('change', toggleSelectCourseRow);

	$coursesTbody?.appendChild($row);
}

function toggleSelectCourseRow() {
	const $row = this.closest('.course-row');

	if(this.checked) {
		selectedRows[this.value] = $row;

		if(Object.keys(selectedRows).length === 1) {
			$delCourseBtn?.removeAttribute('disabled');
		}
	}
	else {
		delete selectedRows[this.value];

		if(Object.keys(selectedRows).length === 0) {
			$delCourseBtn?.setAttribute('disabled', true);
		}
	}
}

function openDeleteLevelPopup() {
	const $s = $deletePopup?.querySelector('article p span');

	if($s) {
		$s.innerText = "ce niveau";
	}

	action = 'level';

	$deletePopup?.classList.remove('hidden');
}

function openDeleteCoursePopup() {
	const $s = $deletePopup?.querySelector('article p span');

	if($s) {
		$s.innerText = (Object.keys(selectedRows).length > 1)? "ces matières" : "cette matière";
	}

	action = 'course';

	$deletePopup?.classList.remove('hidden');
}


function cancelDeletion() {
	action = null;
	$deletePopup.classList.add('hidden');
}

function confirmDeletion() {
	switch(action) {
		case 'level':
			deleteLevel();
			break;
		case 'course':
			deleteCourse();
			break;
	}

	action = null;
	$deletePopup.classList.add('hidden');
}

async function deleteLevel() {
	try {
		await DELETE(`/api/cursus/${cursusId}/levels/${currentLevel}`);
		
		deleteLevelRow(currentLevel);

		createToast('Niveau supprimé', true, 2000);

		unselectLevel();

		action = null;
		$deletePopup.classList.add('hidden');
	}
	catch(e) {
		console.error(e);
		createToast('Une erreur est survenue', false, 2000);
	}
}

async function deleteCourse() {
	try {
		await POST(`/api/cursus/${cursusId}/levels/${currentLevel}`, {
			courses: [[], Object.keys(selectedRows)]
		});

		for(const courseId in selectedRows) {
			selectedRows[courseId].remove();
			
			const idx = levels[currentLevel].courses.indexOf(+courseId);

			if(idx > -1) {
				levels[currentLevel].courses.splice(idx, 1);
			}
		}

		selectedRows = {};

		if(levels[currentLevel].courses.length === 0) {
			$addCourseBtn?.classList.add('hidden');
			$pEmptyLevel?.classList.remove('hidden');
		}

		createToast('Matière enlevée', true, 2000);

		action = null;
		$deletePopup.classList.add('hidden');
	}
	catch(e) {
		console.error(e);
		createToast('Une erreur est survenue', false, 2000);
	}
}

function showInputLevelCreation() {
	if($iptLvlCrt !== null) {
		$iptLvlCrt.input.focus();
		return;
	}


	const $iptContainer = document.createElement('div');
	$iptContainer.classList.add('ipt-crt-lvl-container');

	const $ipt = document.createElement('input');
	$ipt.classList.add('ipt-crt-lvl-name');
	$ipt.setAttribute('placeholder', 'Nom');
	$ipt.setAttribute('type', 'text');

	const $cancel = document.createElement('button');
	const $confirm = document.createElement('button');

	$cancel.className = 'text nohref danger btn-cancel';
	$confirm.className = 'text nohref success btn-confirm';


	$iptContainer.appendChild($ipt);
	$iptContainer.appendChild($cancel);
	$iptContainer.appendChild($confirm);
	
	$levelsList?.insertAdjacentElement('afterend', $iptContainer);
	$createLevelBtn.classList.add('hidden');

	$ipt.focus();

	$cancel.addEventListener('click', cancelLevelCreation);
	$confirm.addEventListener('click', confirmLevelCreation);


	$iptLvlCrt = {
		container: $iptContainer,
		input: $ipt
	};
}

function cancelLevelCreation() {
	$iptLvlCrt?.container.remove();
	$iptLvlCrt = null;
	$createLevelBtn.classList.remove('hidden');
}

async function confirmLevelCreation() {
	if(!$iptLvlCrt) {
		return;
	}

	try {
		const levelName = $iptLvlCrt.input.value.trim();

		if(levelName.length === 0) {
			return;
		}

		const levelId = (await PUT(`/api/cursus/${cursusId}/levels`, { name: levelName })).levelId;

		$iptLvlCrt.container.remove();
		$iptLvlCrt = null;


		addLevelRow(levelId, levelName);

		$createLevelBtn.classList.remove('hidden');

		createToast('Niveau créé', true, 2000);
	}
	catch(e) {
		console.error(e);
		createToast('Une erreur est survenue', false, 2000);
	}
}

function addLevelRow(levelId, levelName) {
	const $tr = document.createElement('tr');

	$tr.dataset.id = levelId;
	$tr.innerHTML = `<td>${levelName}</td>`;

	$levelsTbody?.appendChild($tr);
}

function deleteLevelRow(levelId) {
	$levelsTbody?.querySelector(`tr[data-id="${levelId}"]`)?.remove();
}

function unselectLevel() {
	unselectCourses();

	$coursesTbody.innerHTML = '';


	$levelsTbody?.querySelector('.selected')?.classList.remove('selected');

	currentLevel = null;

	$pEmptyLevel.innerHTML = 'Sélectionnez un niveau pour voir ses détails.';
	$pEmptyLevel.classList.remove('hidden');

	$coursesTbody?.classList.add('hidden');
	$addCourseBtn?.classList.add('hidden');

	$delLevelBtn.setAttribute('disabled', true);
}

function unselectCourses() {
	$delCourseBtn.setAttribute('disabled', true);

	for(const courseId in selectedRows) {
		const $trIpt = $coursesTbody?.querySelector(`.course-row[data-id="${courseId}"] input[type="checkbox"]`);

		if($trIpt) {
			$trIpt.checked = false;
		}
	}

	selectedRows = {};
}

async function openCourseAddPopup() {
	const $iptContainer = await createCourseAddInput();

	$addCourseBtn.classList.add('hidden');
	
	$coursesTbody?.appendChild($iptContainer);
}

async function openCourseAddPopupEmptyLevel() {
	const $iptContainer = await createCourseAddInput();

	$pEmptyLevel.classList.add('hidden');
	$coursesTbody.classList.remove('hidden');

	$coursesTbody?.appendChild($iptContainer);
}

async function createCourseAddInput() {
	const $container = document.createElement('div');
	$container.className = 'course-row course-add-form';

	let options = [];

	try {
		const _options = (alreadyFetchCourses? Object.values(courses) : (await GET('/api/courses')))
			.map(c => {
				courses[c.id] = c;
				return {
					value: c.id,
					label: c.name
				};
			})
			.filter(c => !levels[currentLevel].courses.includes(c.value));

		options = _options;

		alreadyFetchCourses = true;
	}
	catch(e) {
		console.error(e);
		createToast('Une erreur est survenue, lors de la récupération des matières', false, 2000);
	}

	const $ipt = createSelect('Sélectionner une matière', options);
	$ipt.classList.add('slct-add-course');

	const $confirmBtn = document.createElement('button');
	const $cancelBtn = document.createElement('button');

	$confirmBtn.className = 'text nohref success btn-confirm';
	$cancelBtn.className = 'text nohref danger btn-cancel';

	$cancelBtn.addEventListener('click', () => cancelCourseAdd($container));
	$confirmBtn.addEventListener('click', () => confirmCourseAdd($container, $ipt));


	$container.append($ipt, $confirmBtn, $cancelBtn);

	return $container;
}

async function confirmCourseAdd($container, $slct) {
	const value = +$slct.dataset.value;

	if(!value) {
		return;
	}

	try {
		await POST(`/api/cursus/${cursusId}/levels/${currentLevel}`, {
			courses: [[value]]
		});

		levels[currentLevel].courses.push(value);

		$container.remove();

		addCourseRow(value);

		$addCourseBtn.classList.remove('hidden');

		createToast('Matière ajoutée', true, 2000);
	}
	catch(e) {
		console.error(e);
		createToast('Une erreur est survenue lors de l\'ajout d\'une matière', false, 2000);
	}
}

function cancelCourseAdd($container) {
	$container.remove();

	if(levels[currentLevel].courses.length === 0) {
		$pEmptyLevel.classList.remove('hidden');
		$coursesTbody.classList.add('hidden');
	}
	else {
		$addCourseBtn.classList.remove('hidden');
	}
}