/**
 * @copyright Copyrights (C) 2023 Arthur GROS All rights reserved.
 * @author Arthur GROS
 * @since 2023
 * @package uha.archi_web
 */

import { POST } from './ajax';
import { sendFeedback } from './script';

const $form = document.body.querySelector('form');
const $oldPsswd = $form?.querySelector('#ipt-reset-old');
const $newPsswd1 = $form?.querySelector('#ipt-reset-new1');
const $newPsswd2 = $form?.querySelector('#ipt-reset-new2');

if ($form && $oldPsswd && $newPsswd1 && $newPsswd2)
{
    $form.addEventListener('submit', async e => {
        e.preventDefault();

        $form.classList.add('busy');

        await reset();

        $form.classList.remove('busy');
    })
}

async function reset()
{
    const oldpasswd = $oldPsswd.value;
    const newpasswd1 = $newPsswd1.value;
    const newpasswd2 = $newPsswd2.value;

    if (newpasswd1 !== newpasswd2)
    {
        sendFeedback("Erreur de confirmation de mots de passe", false);
        return false;
    }

    try {
        await POST (window.location.href, { oldpasswd, newpasswd1 });
        sendFeedback("Mot de passe modifié avec succès, redirection...");
        setTimeout(() => window.location.reload(), 1000);
    } 
    catch(e) {
        //console.error(e);
        sendFeedback("Echec de modification du mot de passe", false);
        return false;
    }

    return true;
}