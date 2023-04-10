/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */


export const nextStepEvent = new Event('nextStep');
export const prevStepEvent = new Event('prevStep');

export let common = {};

export function cancelStep() {
	document.dispatchEvent(prevStepEvent);
}