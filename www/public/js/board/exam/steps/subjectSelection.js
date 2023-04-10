/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

import { GET } from "../../../ajax";
import { updateRange } from '../../../form';
import { createToast, page } from "../../../script";
import { common, nextStepEvent } from "../common";
import { renderSubject } from "../renderSubject";


let $chaptersW, $chapters, $generateBtn, $maxQuestions, $iptQuestionsCount, $iptQuestionPerc, $questionPerc, $subjectsWrp, $iptExamName, $iptExamType;
let courseName;

let rangeMinPerc, rangeMaxPerc;

let subjects;
let selectedChapters;

let chaptersCount;
//
let questionsCount;
let questionsPerc;
//
let maxQuestionsCount;
//
let maxTextCount;
let maxMcqCount;
// range bounds
let maxTextPerc;
let maxMcqPerc;
//
let examType;
let examName;


export function load() {
    $chaptersW = document.getElementById('chapters-selection-wrapper');
    $chapters = $chaptersW?.querySelector('ul');
    $generateBtn = $chaptersW?.querySelector('#btn-generate');
    $maxQuestions = document.getElementById('max-questions-count');
    $iptQuestionsCount = document.getElementById('ipt-question-count');
    $iptQuestionPerc = document.getElementById('ipt-question-type-perc');
    $questionPerc = document.getElementById('question-perc');
    $subjectsWrp = document.getElementById('subjects-wrapper');
    $iptExamName = document.getElementById('ipt-exam-name');
    $iptExamType = document.getElementById('exam-type');

    courseName = $chaptersW.querySelector('.course-name').textContent.trim();

    $chapters?.addEventListener('click', toggleChapter);
    $iptQuestionsCount?.addEventListener('keyup', verifyQuestionsCount);
    $iptQuestionPerc?.addEventListener('input', updateQuestionPerc);
    $generateBtn?.addEventListener('click', generateSubjects);
    $iptExamName?.addEventListener('keyup', updateExamName);
    $iptExamType?.querySelectorAll('input[type="radio"]').forEach($ipt => $ipt.addEventListener('change', updateExamType));

    window.addEventListener('scroll', verifyScroll);

	subjects = null;
	selectedChapters = {};

	chaptersCount = 0;
	//
	questionsCount = 0;
	questionsPerc = 50;
	//
	maxQuestionsCount = 0;
	//
	maxTextCount = 0;
	maxMcqCount = 0;
	// range bounds
	maxTextPerc = 0;
	maxMcqPerc = 100;
	//
	examType = null;
	examName = '';

	$iptQuestionsCount.value = questionsCount;
	$iptQuestionPerc.value = questionsPerc;

    verifyScroll();
	updateQuestionPerc();

    document.getElementById('content').style.paddingBottom = 0;

	if('subject' in common) {
		restoreState();
	}


    // DEV - preselect everything
    // $chapters.querySelectorAll('li').forEach($li => toggleChapter({ target: $li }));
    // $iptQuestionsCount.value = '15';
    // $iptExamName.value = 'Super exam';
    // examName = 'Super exam';
    // $iptExamType.children[0].children[0].click();
    // verifyQuestionsCount();
    // $generateBtn.click();
	
	// setTimeout(() => document.querySelector('.btn-next-step')?.click(), 500);
}

export function unload() {
    window.removeEventListener('scroll', verifyScroll);

    $chapters?.removeEventListener('click', toggleChapter);
    $iptQuestionsCount?.removeEventListener('keyup', verifyQuestionsCount);
    $iptQuestionPerc?.removeEventListener('input', updateQuestionPerc);
    $generateBtn?.removeEventListener('click', generateSubjects);
    $iptExamName?.removeEventListener('keyup', updateExamName);
    $iptExamType?.querySelectorAll('input[type="radio"]').forEach($ipt => $ipt.removeEventListener('change', updateExamType));
}


function verifyGenerateState() {
	if(
		chaptersCount > 0 && 0 < questionsCount
		&& questionsCount <= maxQuestionsCount
		&& examType !== null
		&& examName.length >= 3
	) {
		if($generateBtn?.hasAttribute('disabled')) {
			$generateBtn?.removeAttribute('disabled');
		}
	}
	else {
		if(!$generateBtn?.hasAttribute('disabled')) {
			$generateBtn?.setAttribute('disabled', true);
		}
	}
}


function toggleChapter(e) {
	if(e.target.nodeName === 'LI') {
		const $li = e.target;
		const pos = +$li.dataset.i;
		const maxQ = +$li.dataset.maxQuestions;
		const maxQTxt = +$li.dataset.textCount;
		const maxQMcq = +$li.dataset.mcqCount;

		$li.classList.toggle('selected');

		if($li.classList.contains('selected')) {
			selectedChapters[pos] = $li;

			chaptersCount++;
			
			maxQuestionsCount += maxQ;
			maxTextCount += maxQTxt;
			maxMcqCount += maxQMcq;
		}
		else {
			delete selectedChapters[pos];

			chaptersCount--;

			maxQuestionsCount -= maxQ;
			maxTextCount -= maxQTxt;
			maxMcqCount -= maxQMcq;
		}

		$maxQuestions.innerText = maxQuestionsCount;

		verifyQuestionsCount();
		updateQuestionPerc();
		verifyGenerateState();
	}
}

function verifyQuestionsCount() {
	const svalue = $iptQuestionsCount.value.trim();
	const value = +svalue;

	if(value+'' !== svalue) {
		$iptQuestionsCount.value = value;
	}

	if(!/\d+/.test(value)) {
		$iptQuestionsCount.classList.add('danger');
		questionsCount = 0;
		verifyGenerateState();
		return;
	}

	questionsCount = value;

	if(value > maxQuestionsCount) {
		$iptQuestionsCount.classList.add('danger');
		questionsCount = 0;
		verifyGenerateState();
		return;
	}

	$iptQuestionsCount.classList.remove('danger');

	if(questionsCount === 0) {
		maxTextPerc = 50;
		maxMcqPerc = 50;
	}
	else {
		if(maxTextCount < questionsCount) {
			maxTextPerc = Math.floor(maxTextCount * 100 / questionsCount);
		}
		else if(maxTextCount >= questionsCount) {
			maxTextPerc = 100;
		}

		if(maxMcqCount < questionsCount) {
			maxMcqPerc = Math.floor(maxMcqCount * 100 / questionsCount);
		}
		else if(maxMcqCount >= questionsCount) {
			maxMcqPerc = 100;
		}
	}

	rangeMinPerc = 50 - maxTextPerc/2;
	rangeMaxPerc = 50 + maxMcqPerc/2;

	$iptQuestionPerc.parentElement.style.setProperty('--min', `${rangeMinPerc}%`);
	$iptQuestionPerc.parentElement.style.setProperty('--max', `${rangeMaxPerc}%`);

	updateQuestionPerc();
	verifyGenerateState();
}

function updateQuestionPerc() {
	let value = +$iptQuestionPerc.value;

	if(questionsCount === 0) {
		$iptQuestionPerc.value = 50;
		value = 50;
	}
	else {
		if(value < rangeMinPerc) {
			$iptQuestionPerc.value = rangeMinPerc;
			value = rangeMinPerc;
		}

		if(value > rangeMaxPerc) {
			$iptQuestionPerc.value = rangeMaxPerc;
			value = rangeMaxPerc;
		}
	}

	questionsPerc = value;

	$questionPerc.innerText = `${100 - value}% - ${value}%`;
}

async function generateSubjects() {
	const firstGen = subjects === null;

	$generateBtn.setAttribute('disabled', true);

	if(firstGen) {
		subjects = [];
	}

	try {
		const params = [
			`courseId=${page.query.course}`,
			`chapters=${Object.keys(selectedChapters).join(',')}`,
			`qCount=${questionsCount}`,
			`mcqPerc=${questionsPerc}`,
		].join('&');

		subjects = await GET('/api/exams/generate?' + params);

		subjects.chapters = subjects.chapters.sort((a, b) => a.id < b.id ? -1 : 1);

		displaySubjects();

		if(firstGen) {
			$generateBtn.innerText = 'Régénérer';
		}
	}
	catch(e) {
		console.error(e);
		createToast('Une erreur est survenu', false, 2000);
	}
	finally {
		$generateBtn.removeAttribute('disabled');
	}
}

function displaySubjects() {
	$subjectsWrp.innerHTML = '';

	const $tabsWrp = document.createElement('ul');
	$tabsWrp.classList.add('subjects-navigation');

	const $subjectContentWrp = document.createElement('div');
	$subjectContentWrp.classList.add('inner');

	let i = 0;

	for(const questions of subjects.subjects) {
		const $tab = document.createElement('li');
		$tab.dataset.n = ++i;

		$tabsWrp.appendChild($tab);

		$tab.innerText = `Sujet ${i}`;

		const $subjectTabContent = document.createElement('div');
		$subjectTabContent.classList.add('subject-content', 'hidden');
		$subjectTabContent.dataset.n = i;

		$subjectContentWrp.appendChild($subjectTabContent);

		$tab.addEventListener('click', () => {
			const $slct = $tabsWrp.querySelector('.selected');

			if($slct) {
				$slct?.classList.remove('selected');
				const $sbjct = $subjectContentWrp.querySelector(`.subject-content[data-n="${$slct.dataset.n}"]`);
				$sbjct?.classList.add('hidden');
			}
			
			$tab.classList.add('selected');
			$subjectTabContent.classList.remove('hidden');
		});

		const $subject = renderSubject(transformSubject(subjects.chapters, questions));

		$subjectTabContent.appendChild($subject);
	}


	const $btnNextStep = document.createElement('button');
	$btnNextStep.className = 'btn-next-step primary nohref';
	$btnNextStep.innerText = "Sélectionner ce sujet";
	$btnNextStep.addEventListener('click', selectSubject);


	$tabsWrp.childNodes.item(0).classList.add('selected');
	$subjectContentWrp.childNodes.item(0).classList.remove('hidden');

	$subjectsWrp.append($tabsWrp, $subjectContentWrp, $btnNextStep);
}

function transformSubject(chapters, questions) {
	return {
		overview: {
			name: examName,
			type: examType,
			year: new Date().getFullYear(),
			courseName,
			courseId: +page.query.course,
			coeff: 1
		},
		chapters,
		questions
	};
}

function updateExamName() {
	examName = $iptExamName.value.trim();
	verifyGenerateState();
}

function updateExamType(e) {
	if(e.target.checked) {
		examType = +e.target.value;
	}

	verifyGenerateState();
}


function verifyScroll() {
	const top = $chaptersW.getBoundingClientRect().top;

	if(top <= 20) {
		$chaptersW.classList.add('filled');
	}
	else {
		$chaptersW.classList.remove('filled');
	}
}

function selectSubject() {
	const n = +$subjectsWrp.querySelector('.subjects-navigation .selected').dataset.n;
	
	if(0 < n && n <= subjects.subjects.length) {
		let subject = transformSubject(subjects.chapters, subjects.subjects[n-1]);

		subject.questions = subject.questions
			.sort((a, b) => {
				if(a.chapterId === b.chapterId)
					return Math.sign(a.id - b.id);
				
				return Math.sign(a.chapterId - b.chapterId);
			});

		common.subject = subject;
		common.generatedSubjects = subjects;
		common.settings = {
			qcmPerc: questionsPerc
		};
		
		if('marks' in common) {
			delete common.marks;
		}

		document.dispatchEvent(nextStepEvent);
	}
}

function restoreState() {
	subjects = common.generatedSubjects;

	for(let chapter of subjects.chapters) {
		const $li = $chapters.querySelector(`li[data-id="${chapter.id}"]`);

		if($li) {
			toggleChapter({ target: $li });
		}
	}

	questionsCount = subjects.subjects[0].length;
	chaptersCount = subjects.chapters.length;
	examType = common.subject.overview.type;
	examName = common.subject.overview.name;

	$iptQuestionsCount.value = questionsCount;
	$iptQuestionPerc.value = common.settings.qcmPerc;

	$iptExamName.value = examName;
	$iptExamType.querySelector(`input[value="${examType}"]`)?.click();

	verifyQuestionsCount()
	updateRange($iptQuestionPerc);


	displaySubjects();
}