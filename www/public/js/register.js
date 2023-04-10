/**
 * @copyright Copyrights (C) 2023 Arthur GROS All rights reserved.
 * @author Arthur GROS
 * @since 2023
 * @package uha.archi_web
 */

import { PUT } from './ajax';
import { sendFeedback } from './script';


const $form = document.body.querySelector('form');
const $university = $form?.querySelector('#ipt-reg-id');
const $domain = $form?.querySelector('#ipt-reg-domain');
const $passwd = $form?.querySelector('#ipt-reg-psswd');



if ($form && $university && $domain && $passwd)
{
    $form.addEventListener('submit', async e => {
        e.preventDefault();

        $form.classList.add('busy');

        await register();

        $form.classList.remove('busy');
    })
}

async function register()
{
    const university = $university.value;
    const domain = $domain.value.trim().toLowerCase();
    const password = $passwd.value;


    if (password.length === 0 || password.length > 63)
        return false;

    //request
    try {
        await PUT(window.location.href, { university, domain, password });

        sendFeedback(`Inscription réussie, connectez-vous avec admin@${domain}`);
    }
    catch(e) {
        console.error(e);
        sendFeedback(`L'université ${university} existe déjà avec ce domaine`, false);
        return false;
    }

    return true;
}