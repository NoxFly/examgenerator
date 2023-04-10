/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

import { page, updateUrl } from '../../../script';
import { nextStepEvent } from '../common';

let $select;

/**
 * 
 * @param {HTMLElement} $main 
 */
export function load($main) {
	$select = document.getElementById('slct-course');
	$select?.addEventListener('changed', selectCourse);
}

export function unload() {
	$select?.removeEventListener('changed', selectCourse);
}


function selectCourse() {
	const id = +$select.dataset.value;
	
	if(id > 0) {
		page.query.course = id;
		updateUrl();
		document.dispatchEvent(nextStepEvent);
	}
}