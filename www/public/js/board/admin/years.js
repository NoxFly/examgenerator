/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

import { PUT } from '../../ajax';
import { createToast } from '../../script';
import { $actions, $actionsPanel, $newBtn, $tbody, updatePagination } from './adminBoardListManager';



function openCreationPopup() {
	$yearSLCT.reset();
	$creationPopup?.classList.remove('hidden');
}

function closeCreationPopup() {
	$creationPopup?.classList.add('hidden');
}

function confirmCreationPopup() {
	const year = +$yearSLCT?.dataset.value;

	if(year > 0) {
		try {
			createYear(year);

			$yearSLCT.removeOption(year);

			addYearRow(year);

			pagination.size++;
			pagination.total++;
			updatePagination(pagination);

			createToast('Année créée', true, 2000);

			$creationPopup?.classList.add('hidden');
		}
		catch(e) {
			console.error(e);
			createToast('Une erreur est survenue', false, 2000);
		}
	}
}

/**
 * @param {number}
 */
function addYearRow(year) {
	const $tr = document.createElement('tr');

	$tr.innerHTML = `<td>${year} - ${year+1}</td>`;
	$tr.dataset.year = year;

	let $rowToAppend = null;

	for(const $row of $tbody.children) {
		const rowYear = +$row.dataset.year;

		if(rowYear < year) {
			$rowToAppend = $row;
			break;
		}
	}

	if($rowToAppend) {
		$tbody.insertBefore($tr, $rowToAppend);
	}
	else {
		$tbody.appendChild($tr);
	}
}

/**
 * 
 * @param {number} year 
 */
async function createYear(year) {
	await PUT('/api/university/years', { year });
}





const $resultCount = $actionsPanel?.querySelector('.result-count span:first-child');
const $totalResultCount = $actionsPanel?.querySelector('.result-count span:last-child');

const $page = $actions?.querySelector('p span:first-child');
const $totalPages = $actions?.querySelector('p span:last-child');

const $creationPopup = document.querySelector('.year-creation-popup');
const $yearSLCT = document.querySelector('#slct-year');

$creationPopup?.querySelector('.btn-cancel')?.addEventListener('click', closeCreationPopup);
$creationPopup?.querySelector('.btn-confirm')?.addEventListener('click', confirmCreationPopup);

$newBtn.addEventListener('click', openCreationPopup);

const pagination = {
	size: +$resultCount?.textContent.trim(),
	total: +$totalResultCount?.textContent.trim(),
	page: +$page?.textContent.trim(),
	maxPage: +$totalPages?.textContent.trim()
};