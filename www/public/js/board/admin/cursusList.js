/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

import { DELETE, POST, PUT } from '../../ajax';
import { createToast } from '../../script';
import { selectedRows, $tbody, $newBtn, $delBtn, $actions, $actionsPanel, updatePagination, resetSelectedRows, $editBtn } from './adminBoardListManager';

$tbody.addEventListener('click', e => {
	if(e.target.nodeName === 'TD') {
		const $tr = e.target.closest('tr');
		window.location.href = window.location.href + '/' + $tr.dataset.id;
	}
}, false);


function openCreationPopup() {
	action = 'create';
	$iptName.value = '';
	$creationPopup?.classList.remove('hidden');
}

function openEditionPopup() {
	action = 'edit';

	const row = Object.entries(selectedRows)[0];

	editing.id = +row[0];
	editing.tr = row[1].querySelector('.cursus-name');
	editing.name = editing.tr.textContent?.trim();

	$iptName.value = editing.name;
	$creationPopup?.classList.remove('hidden');
}

function openDeletionPopup() {
	$deletionPopup?.classList.remove('hidden');
}

function cancelCreation() {
	$creationPopup?.classList.add('hidden');
}

function cancelDeletion() {
	$deletionPopup?.classList.add('hidden');
}

async function confirmCreation() {
	if(action === 'edit') {
		return confirmEdition();
	}

	const name = ($iptName?.value || '').trim();

	if(name.length === 0) {
		return;
	}

	try {
		const cursusId = (await createCursus(name)).cursusId;
		addCursusRow(cursusId, name);

		pagination.size++;
		pagination.total++;
		updatePagination(pagination);

		createToast('Cursus créé', true, 2000);
		$creationPopup?.classList.add('hidden');
	}
	catch(e) {
		console.error(e);
		createToast('Une erreur est survenue', false, 2000);
	}
}

async function confirmDeletion() {
	try {
		for(const cursusId in selectedRows) {
			await deleteCursus(cursusId);
			deleteCursusRow(cursusId);

			pagination.size--;
			pagination.total--;

			updatePagination(pagination);
		}

		resetSelectedRows();

		$editBtn?.setAttribute('disabled', true);
		$delBtn?.setAttribute('disabled', true);

		createToast('Cursus supprimé(s)', true, 2000);
		$deletionPopup?.classList.add('hidden');
	}
	catch(e) {
		console.error(e);
		createToast('Une erreur est survenue', false, 2000);
	}
}

async function confirmEdition() {
	const newName = $iptName.value.trim();

	if(editing === newName) {
		return;
	}

	try {
		await POST('/api/cursus/' + editing.id, { name: editing.name });

		editing.tr.innerText = newName;

		createToast('Cursus mis à jour', true, 2000);
		$creationPopup?.classList.add('hidden');
	}
	catch(e) {
		console.error(e);
		createToast('Une erreur est survenue', false, 2000);
	}
}

async function createCursus(name) {
	return await PUT('/api/cursus', { name });
}

function addCursusRow(id, name) {
	const $tr = document.createElement('tr');

	$tr.dataset.id = id;

	$tr.innerHTML = `<td class="select-box">
		<div>
			<input type ="checkbox" value="${id}"/>
		</div>
	</td>
	<td class="cursus-name">${name}</td>`;

	$tbody?.appendChild($tr);
}

async function deleteCursus(id) {
	return await DELETE('/api/cursus/' + id);
}

function deleteCursusRow(id) {
	$tbody.querySelector(`tr[data-id="${id}"]`)?.remove();
}



const $creationPopup = document.querySelector('.cursus-creation-popup');
const $deletionPopup = document.querySelector('.delete-popup');

const $cancelCreationBtn = $creationPopup?.querySelector('.btn-cancel');
const $confirmCreationBtn = $creationPopup?.querySelector('.btn-confirm');

const $cancelDeletionBtn = $deletionPopup?.querySelector('.btn-cancel');
const $confirmDeletionBtn = $deletionPopup?.querySelector('.btn-confirm');

const $resultCount = $actionsPanel?.querySelector('.result-count span:first-child');
const $totalResultCount = $actionsPanel?.querySelector('.result-count span:last-child');

const $page = $actions?.querySelector('p span:first-child');
const $totalPages = $actions?.querySelector('p span:last-child');

const $iptName = $creationPopup?.querySelector('input[name="cursus-name"]');


$newBtn.addEventListener('click', openCreationPopup);
$editBtn.addEventListener('click', openEditionPopup);
$delBtn.addEventListener('click', openDeletionPopup);

$cancelCreationBtn?.addEventListener('click', cancelCreation);
$confirmCreationBtn?.addEventListener('click', confirmCreation);

$cancelDeletionBtn?.addEventListener('click', cancelDeletion);
$confirmDeletionBtn?.addEventListener('click', confirmDeletion);


const pagination = {
	size: +$resultCount?.textContent.trim(),
	total: +$totalResultCount?.textContent.trim(),
	page: +$page?.textContent.trim(),
	maxPage: +$totalPages?.textContent.trim()
};

let action = null;
let editing = {
	id: null,
	tr: null,
	name: null
};