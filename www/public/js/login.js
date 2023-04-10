/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

import { POST } from './ajax';
import { sendFeedback } from './script';


const $form = document.body.querySelector('form');
const $login = $form?.querySelector('#ipt-login-id');
const $passwd = $form?.querySelector('#ipt-login-psswd');


if($form && $login && $passwd) {
	$form.addEventListener('submit', async e => {
		e.preventDefault();

		$form.classList.add('busy');

		await login();

		$form.classList.remove('busy');
	});
}


async function login() {
	const username = $login.value.trim().toLowerCase();
	const password = $passwd.value;

	// check
	if(!/^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/.test(username) || password.length === 0 || password.length > 63) {
		return false;
	}

	// request
	try {
		await POST(window.location.href, { username, password });

		sendFeedback('Connexion réussie.');
		// reload on success (no exception thrown)
        window.location.reload();
	}
	catch(e) {
		console.error(e);
		sendFeedback('Identifiant ou mot de passe incorrect.', false);
		return false;
	}

	return true;
}