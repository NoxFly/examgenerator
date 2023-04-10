/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */


export const page = {
	url: window.location.pathname,
	baseUrl: window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/www/') + 4), // DEV ONLY
	query: {}
};

{
	const tmpQuery = window.location.search.slice(1).split('&').filter(e => !!e).map(e => e.split('='));
	
	for(const q of tmpQuery) {
		if(isNaN(q[1])) {
			page.query[q[0]] = q[1];
		}
		else {
			page.query[q[0]] = Number(q[1]);
		}
	}
}


export function serialiseQuery() {
	const seria = Object.entries(page.query).map(e => `${e[0]}=${e[1]}`).join('&');
	return (seria.length > 0)? '?' + seria : '';
}

export function updateUrl() {
	history.replaceState(null, null, page.url + serialiseQuery());
}



/**
 * 
 * @param {string} txt 
 * @param {boolean} success 
 */
export function sendFeedback(txt, success=true) {
    const state = success? 'success' : 'danger';

	const $feedback = document.body.querySelector('.feedback-message');

	if($feedback) {
		$feedback.dataset.state = state;
		$feedback.innerText = txt;
	}
}


/**
 * 
 * @param {string} message 
 * @param {boolean} success 
 * @param {number} duration
 */
export function createToast(message, success, duration=0) {
	const toast = document.createElement('div');
	toast.className = 'toast ' + ((success)?'success':'fail');

	toast.innerHTML = `<div></div><p>${message}</p>`;

	document.body.appendChild(toast);

	let step = 0;

	if(duration > 0) {
		toast.onanimationend = () => {
			if(++step === 1) {
				setTimeout(() => {
					toast.classList.add('disappearing');
				}, duration);
			}
			else {
				toast.remove();
			}
		};
	}
}