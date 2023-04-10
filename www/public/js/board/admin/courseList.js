/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

import { DELETE, GET, POST, PUT } from '../../ajax';
import { createToast } from '../../script';
import { selectedRows, $tbody, $filterBar, $newBtn, $editBtn, $delBtn, updatePagination, resetSelectedRows } from './adminBoardListManager';


let courses = {};

const pagination = {
	size: 0,
	total: 0,
	page: 1,
	maxPage: 1
};

const filters = {
	years: null,
	cursus: null,
	withoutReferent: false,
	currentYear: false
};

let action = null;
let editing = {};

const currentYear = +$tbody.dataset.year;
delete $tbody.dataset.year;


async function fetchCourses() {
	try {
		const fcourses = await GET('/api/courses');

		pagination.total = fcourses.length;
		pagination.size = pagination.total;

		updatePagination(pagination);

		$tbody.innerHTML = '';

		for(const course of fcourses) {
			courses[course.id] = course;
			addCourseRow(course);
		}
	}
	catch(e) {
		console.error(e);
		createToast('Une erreur est survenue', false, 2000);
	}
}



//

function openCreationPopup() {
	action = 'create';

	$iptName.value = '';
	$slctRef?.reset();

	$creationPopup?.classList.remove('hidden');
}

function openEditionPopup() {
	action = 'edit';

	editing = courses[+Object.keys(selectedRows)[0]];

	if(!editing) {
		return;
	}

	$iptName.value = editing.name;

	$slctRef?.reset();

	if(editing.referent) {
		$slctRef?.select(editing.referent.teacherId);
	}
	else {
		$slctRef?.select('none');
	}

	$creationPopup?.classList.remove('hidden');
}

function openDeletionPopup() {
	$deletionPopup?.classList.remove('hidden');
}

function cancelCreationPopup() {
	if(action === 'edit') {
		return cancelEditionPopup();
	}

	$creationPopup?.classList.add('hidden');
}

function cancelEditionPopup() {
	if(action !== 'edit') {
		return;
	}

	editing = {};

	$creationPopup?.classList.add('hidden');
}

function cancelDeletionPopup() {
	$deletionPopup?.classList.add('hidden');
}

async function confirmCreationPopup() {
	if(action === 'edit') {
		return confirmEditionPopup();
	}

	const data = {
		name: ($iptName?.value || '').trim(),
		referent: $slctRef?.dataset.value || null
	};

	if(data.name.length === 0) {
		return;
	}

	if(!data.referent || data.referent === 'none') {
		delete data.referent;
	}

	try {
		const course = await addCourse(data);
		addCourseRow(course);

		pagination.size++;
		pagination.total++;

		updatePagination(pagination);

		$creationPopup?.classList.add('hidden');
		createToast('Matière créée', true, 2000);
	}
	catch(e) {
		console.error(e);
		createToast('Une erreur est survenue', false, 2000);
	}
}

async function confirmEditionPopup() {
	if(action !== 'edit') {
		return;
	}

	const data = {
		name: ($iptName?.value || '').trim(),
		referent: $slctRef?.dataset.value || null
	};

	if(data.name.length === 0) {
		return;
	}

	if(data.name === editing.name) {
		delete data.name;
	}

	if(!data.referent || +data.referent === editing.referent?.teacherId) {
		delete data.referent;
	}

	if(Object.keys(data).length === 0) {
		return;
	}

	try {
		await editCourse(editing.id, data);
		editCourseRow(editing.id, data);

		$creationPopup?.classList.add('hidden');
		createToast('Matière modifiée', true, 2000);
	}
	catch(e) {
		console.error(e);
		createToast('Une erreur est survenue', false, 2000);
	}
}

async function confirmDeletionPopup() {
	try {
		for(const courseId in selectedRows) {
			await deleteCourse(+courseId);

			delete courses[+courseId];

			pagination.size--;
			pagination.total--;

			updatePagination(pagination);

			deleteCourseRow(courseId);
		}

		resetSelectedRows();

		$editBtn?.setAttribute('disabled', true);
		$delBtn?.setAttribute('disabled', true);

		$deletionPopup?.classList.add('hidden');
		createToast('Matière(s) supprimée(s)', true, 2000);
	}
	catch(e) {
		console.error(e);
		createToast('Une erreur est survenue', false, 2000);
	}
}



// ------------------------- CB -------------------------

async function addCourse(data) {
	// if one fails, everything fail, and there is no message to tell what succeed

	const courseId = (await PUT('/api/courses', { name: data.name }))?.courseId;

	if(data.referent) {
		await POST(`/api/courses/${courseId}/referent`, { referentId: data.referent });
	}

	const course = await GET(`/api/courses/${courseId}`);

	courses[courseId] = course;
	return course;
}

function addCourseRow(course) {
	const isCurrent = course.years[0] === currentYear;
	const referent = (!course.referent)? '' : course.referent.lastname + ' ' + course.referent.firstname;

	const $tr = document.createElement('tr');
	$tr.dataset.id = course.id;

	$tr.innerHTML = `<td class="select-box">
		<div>
			<input type="checkbox" name="row" value="${course.id}"/>
		</div>
	</td>
	<td class="course-name">${course.name}</td>
	<td class="course-referent">${referent}</td>
	<td class="course-current-year${isCurrent?' current':''}">${isCurrent? 'oui' : 'non'}</td>
	<td class="course-cursus-count">${course.cursus.length}</td>`;

	$tbody.appendChild($tr);
}

async function editCourse(courseId, course) {
	// if one fails, everything fail, and there is no message to tell what succeed

	if('name' in course) {
		await POST(`/api/courses/${courseId}`, { name: course.name });
		courses[courseId].name = course.name;
	}

	if('referent' in course) {
		if(isNaN(course.referent)) {
			await DELETE(`/api/courses/${courseId}/referent`, { referentId: +course.referent });
			courses[courseId].referent = null;
		}
		else {
			await POST(`/api/courses/${courseId}/referent`, { referentId: +course.referent });
			courses[courseId].referent = await GET(`/api/courses/${courseId}/referent`);
		}
	}
}

async function editCourseRow(courseId, course) {
	const $name = selectedRows[courseId].querySelector('.course-name');
	const $ref = selectedRows[courseId].querySelector('.course-referent');

	if('name' in course) {
		$name.textContent = course.name;
	}

	if('referent' in course && !isNaN(course.referent)) {
		try {
			$ref.textContent = `${courses[courseId].referent.lastname} ${courses[courseId].referent.firstname}`;
		}
		catch(e) {
			console.error(e);
			$ref.textContent = '';
		}
	}
	else {
		$ref.textContent = '';
	}
}

async function deleteCourse(courseId) {
	await DELETE(`/api/courses/${courseId}`);
}

function deleteCourseRow(courseId) {
	$tbody?.querySelector(`tr[data-id="${courseId}"]`)?.remove();
}



// ------------------------- FILTERS -------------------------

function arrayIncludesArray(arr1, arr2) {
	return arr1.filter(e => arr2.includes(e)).length > 0;
}

function applyFilters() {
	const cond = course => (
		(filters.years === null || arrayIncludesArray(filters.years, course.years)) &&
		(filters.cursus === null || arrayIncludesArray(filters.cursus, course.cursus)) &&
		(!filters.withoutReferent || !course.referent) &&
		(!filters.currentYear || course.years[0] === currentYear)
	);

	const l = $tbody.children.length;

	for(let i=0; i < l; i++) {
		const $tr = $tbody.children.item(i);

		const courseId = +$tr.dataset.id;
		const course = courses[courseId];

		if(!course || cond(course)) {
			$tr.classList.remove('hidden');
		}
		else {
			$tr.classList.add('hidden');
		}
	}
}

function filterYear() {
	if(!$yearFLT || !$yearFLT.dataset.value)
		return;

	if($yearFLT.dataset.value === '*') {
		filters.years = null;
	}
	else {
		filters.years = $yearFLT.dataset.value.split(';').map(v => +v);
	}

	applyFilters();
}

function filterCursus() {
	if(!$cursusFLT || !$cursusFLT.dataset.value)
		return;

	if($cursusFLT.dataset.value === '*') {
		filters.cursus = null;
	}
	else {
		filters.cursus = $cursusFLT.dataset.value.split(';').map(v => +v);
	}

	applyFilters();
}

function filterReferent() {
	filters.withoutReferent = $referentFLT?.checked || false;
	applyFilters();
}

function filterCurrentYear() {
	filters.currentYear = $currYearFLT?.checked || false;
	applyFilters();
}


// -------------------------  -------------------------



fetchCourses();


const $creationPopup = document.querySelector('.creation-popup');
const $deletionPopup = document.querySelector('.delete-popup');

const $cancelCreation = $creationPopup?.querySelector('.btn-cancel');
const $confirmCreation = $creationPopup?.querySelector('.btn-confirm');

const $cancelDeletion = $deletionPopup?.querySelector('.btn-cancel');
const $confirmDeletion = $deletionPopup?.querySelector('.btn-confirm');

const $yearFLT = $filterBar?.querySelector('#slct-year');
const $cursusFLT = $filterBar?.querySelector('#slct-cursus');
const $referentFLT = $filterBar?.querySelector('#ipt-without-referent');
const $currYearFLT = $filterBar?.querySelector('#ipt-this-year');

const $iptName = $creationPopup?.querySelector('#ipt-crt-name');
const $slctRef = $creationPopup?.querySelector('#slct-referent');

$yearFLT?.addEventListener('changed', filterYear);
$cursusFLT?.addEventListener('changed', filterCursus);
$referentFLT?.addEventListener('change', filterReferent);
$currYearFLT?.addEventListener('change', filterCurrentYear);

$newBtn?.addEventListener('click', openCreationPopup);
$editBtn?.addEventListener('click', openEditionPopup);
$delBtn?.addEventListener('click', openDeletionPopup);

$cancelCreation?.addEventListener('click', cancelCreationPopup);
$confirmCreation?.addEventListener('click', confirmCreationPopup);

$cancelDeletion?.addEventListener('click', cancelDeletionPopup);
$confirmDeletion?.addEventListener('click', confirmDeletionPopup);