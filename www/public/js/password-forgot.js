/**
 * @copyright Copyrights (C) 2023 Arthur GROS All rights reserved.
 * @author Arthur GROS
 * @since 2023
 * @package uha.archi_web
 */

import { PUT } from './ajax';
import { sendFeedback } from './script';

const $form = document.body.querySelector('form');
const $mail = $form?.querySelector('#ipt-forgot-mail');

if ($form && $mail)
{
    $form.addEventListener('submit', async e=> {
        e.preventDefault();

        $form.classList.add('busy');

        await forgot();

        $form.classList.remove('busy');
    })
}

async function forgot()
{
    const mail = $mail.value;
    const res = await PUT(window.location.href, { mail });

    console.log(res);

    if(res?.password) {
        sendFeedback("Le nouveau mot de passe est : " + res.password, true);
    }
    else {
        sendFeedback("Compte introuvable", false);
    }
}
