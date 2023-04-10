/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

/**
 * @typedef {size: number, total: number, page: number, maxPage: number} Pagination
 */

export let selectedRows = {};

export const $filterBar = document.querySelector('.filter-bar');
export const $resultsView = document.querySelector('.results-view');
export const $actionsPanel = document.querySelector('.actions-panel');

export const $table = $resultsView?.querySelector('.results-view table');
export const $tbody = $table?.querySelector('tbody');
export const $thead = $table?.querySelector('thead');
export const $editBtn = $actionsPanel?.querySelector('.btn-edit-item');
export const $newBtn = $actionsPanel?.querySelector('.btn-new-item');
export const $delBtn = $actionsPanel?.querySelector('.btn-delete-item');
export const $search = $filterBar?.querySelector('.search-bar input');

export const $resultCountWrp = $actionsPanel?.querySelector('.result-count');
const $resultCount = $resultCountWrp?.querySelector('span:first-child');
const $resultTotalCount = $resultCountWrp?.querySelector('span:last-child');

export const $actions = $actionsPanel?.querySelector('.pages-actions');
const $currPage = $actions?.querySelector('p span:first-child');
const $maxPage = $actions?.querySelector('p span:last-child');

export const $pagePreviousBtn = $actions?.querySelector('.left-arrow');
export const $pageNextBtn = $actions?.querySelector('.right-arrow');


export let totalRowsCount = 0;

/**
 * 
 * @param {number} total 
 */
export function updateTotalRowsCount(total) {
	totalRowsCount = total;
}

export function resetSelectedRows() {
	selectedRows = {};

	const $ipt = $thead?.querySelector('input[type="checkbox"]');

	if($ipt?.checked) {
		$ipt.click();
	}
}

/**
* 
* @param {Pagination} pagination 
*/
export function updatePagination(pagination) {
   if($resultCount) {
	   $resultCount.innerText = pagination.size;
   }

   if($resultTotalCount) {
	   $resultTotalCount.innerText = pagination.total;
   }

   if($currPage) {
	   $currPage.innerText = pagination.page;
   }

   if($maxPage) {
	   $maxPage.innerText = pagination.maxPage;
   }

   if(pagination.page == 1) {
	   $pagePreviousBtn?.classList.add('disabled');
   }
   else {
	   $pagePreviousBtn?.classList.remove('disabled');
   }

   if(pagination.page == pagination.maxPage) {
	   $pageNextBtn?.classList.add('disabled');
   }
   else {
	   $pageNextBtn?.classList.remove('disabled');
   }

	updateTotalRowsCount(pagination.size);
}

if($table) {
	totalRowsCount = $table.querySelectorAll('tbody tr').length;

	$table.addEventListener('click', e => {
		const target = e.target;

		if(target.nodeName === 'INPUT') {
			const value = target.value;

			if(value === '*') {
				let fn = $tr => {
					$tr.classList.remove('selected');
					const $ipt = $tr.querySelector('input[type="checkbox"]');
					$ipt.checked = false;
				};

				if(target.checked) {
					fn = $tr => {
						$tr.classList.add('selected');
						const $ipt = $tr.querySelector('input[type="checkbox"]');
						$ipt.checked = true;
						selectedRows[$ipt.value] = $tr;
					}
				}
				else {
					selectedRows = [];
				}

				$table.querySelectorAll('tbody tr').forEach(fn);
			}
			else {
				const $tr = target.closest('tr');

				if(target.checked) {
					$tr.classList.add('selected');
					selectedRows[value] = $tr;

					if(Object.keys(selectedRows).length === totalRowsCount) {
						const $ipt = $table.querySelector('thead .select-box input');

						if($ipt) {
							$ipt.checked = true;
						}
					}
				}
				else {
					$tr.classList.remove('selected');
					delete selectedRows[value];

					const $ipt = $table.querySelector('thead .select-box input');

					if($ipt) {
						$ipt.checked = false;
					}
				}
			}

			//
			const selectedRowsCount = Object.keys(selectedRows).length;

			if(selectedRowsCount === 0) {
				$editBtn?.setAttribute('disabled', true);
				$delBtn?.setAttribute('disabled', true);
			}
			else if(selectedRowsCount === 1) {
				$editBtn?.removeAttribute('disabled');
				$delBtn?.removeAttribute('disabled');
			}
			else {
				$editBtn?.setAttribute('disabled', true);
				$delBtn?.removeAttribute('disabled');
			}
		}
	}, false);
}

if($search) {
	$search.addEventListener('keyup', () => {
		const value = $search.value.trim();
		const regVal = new RegExp(value, 'i');

		let fn = $tr => {
			const trContent = $tr.textContent.replace(/\s+/g, ' ');

			if(regVal.test(trContent)) {
				$tr.classList.remove('hidden');
			}
			else {
				$tr.classList.add('hidden');
			}
		};

		if(value === '') {
			fn = $tr => $tr.classList.remove('hidden');
		}

		$tbody?.querySelectorAll('tr').forEach(fn);
	});
}