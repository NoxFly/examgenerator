/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

import { GET } from "../../ajax";
import { prepareMiddleRange, prepareSelect } from "../../form";
import { createToast, page, serialiseQuery } from "../../script";
import * as step0 from "./steps/selectCourse";
import * as step1 from "./steps/subjectSelection";
import * as step2 from "./steps/markingScheme";
import * as step3 from "./steps/selectLevelsAndDates";
import { cancelStep } from './common';

let step = 0;

const steps = [
	step0,
	step1,
	step2,
	step3
];

if('course' in page.query) {
	step = 1;
}


const $main = document.getElementById('step-content');
const $progress = document.getElementById('step-progress-bar');


function updateProgress() {
	const width = Math.min(Math.max(0, step * 100 / steps.length), 100);
	$progress.style.setProperty('--width', width + '%');
}

async function updateStep(dir=0) {
	if(step < 0) {
		step = 0;
		return;
	}

	const classTransitionName = 'step-transition-';
	const classAppearName = 'step-appear-';
	const transitionClass = ((dir >= 0)? 'forwards' : 'backwards');

	const classDisappear = classTransitionName + transitionClass;
	const classAppear = classAppearName + transitionClass;

	if(step >= steps.length) {
		step = steps.length-1;
		return;
	}

	let content = null;
	const initialDisplay = window.getComputedStyle($main).display;

	function removeKeyframe() {
		$main.classList.remove(classAppear);
	}

	function redisplayStep() {
		$main.style.display = 'none';

		if(dir !== 0) {
			$main.classList.remove(classDisappear);
		}

		$main.classList.add(classAppear);

		$main.addEventListener('animationend', removeKeyframe, { once: true });

		$main.style.display = initialDisplay;
	}

	async function recoverData() {
		content = await GET(`/board/exam/step/${step}` + serialiseQuery(), 'text');
	}

	async function changeStepCD() {
		await recoverData();

		setTimeout(async () => {
			if((dir > 0 && step > 0) || (dir < 0 && step < steps.length - 1)) {
				await steps[step-dir].unload();
			}

			await changeStep();
		}, 200);
	}

	async function changeStep() {
		$main.innerHTML = '';

		document.documentElement.scrollTo({
			top: 0,
			behavior: 'smooth'
		});

		$main.innerHTML = content;
		$main.className = `step-${step}`;

		$main.parentElement.classList.remove(`step-${step}`);

		$main.querySelectorAll('.select').forEach(prepareSelect);
		$main.querySelectorAll('.input-middle-range input[type="range"]').forEach(prepareMiddleRange);

		updateProgress();
		
		if(step < steps.length) {
			await steps[step].load($main);
			document.querySelector('.prev-step')?.addEventListener('click', cancelStep);
		}

		redisplayStep();
	}

	try {
		if(dir !== 0) {
			$main.classList.add(classDisappear);
		}

		if(dir !== 0) {
			$main.addEventListener('animationend', changeStepCD, { once: true });
		}
		else {
			await recoverData();
			changeStep();
		}
	}
	catch(e) {
		createToast('Une erreur est survenue', false, 2000);
		redisplayStep();
	}
}

function nextStep() {
	step++;
	updateStep(1);
}

function prevStep() {
	step--;
	updateStep(-1);
}

updateStep();

document.addEventListener('nextStep', nextStep);
document.addEventListener('prevStep', prevStep);