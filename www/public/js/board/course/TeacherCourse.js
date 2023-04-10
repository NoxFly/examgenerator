/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */
import { createToast, page } from "../../script";
import { GET, DELETE, PUT, POST } from "../../ajax";

/**
 * @typedef {Object} Question
 * @property {number} question.id
 * @property {number} question.chapterId
 * @property {number} question.type
 * @property {string} question.state
 * @property {string|string[]} question.proposals
 * @property {string|string[]} question.answers
 * @property {number} question.createdAt
 * property {number} question.points
 * property {number} question.negPoints
 */


const $article = document.getElementById("chapters-list");

const $createChapterPopup = document.body.querySelector(".create-chapter-popup");
const $deleteChapterPopup = document.body.querySelector("#delete-chapter");
const $createQuestionPopup = document.body.querySelector(".create-question-popup");
const $deleteQuestionPopup = document.body.querySelector("#delete-question");
const $formNewChapter = document.getElementById("chapter-form");
const $formQuestion = $createQuestionPopup?.querySelector('#question-form');
const $questionType = $formQuestion?.querySelector('#question-type');
const $questionState = $formQuestion?.querySelector('.question-state');
const $questionProps = $formQuestion?.querySelector('.question-propositions');
const $questionAns = $formQuestion?.querySelector('.question-answer');


const chapters = {};
const courseId = +page.url.substring(page.url.lastIndexOf('/')+1);



$article?.querySelectorAll('details').forEach($details => {
	$details.addEventListener('toggle', selectChapter);

	const $createQuestionBtn = $details.querySelector('.btn-create-question');
	$createQuestionBtn?.addEventListener('click', createQuestion);

	const $deleteChapterBtn = $details.querySelector('.btn-delete');
	$deleteChapterBtn?.addEventListener('click', deleteChapter);

	const $editChapterBtn = $details.querySelector('.btn-edit');
	$editChapterBtn?.addEventListener('click', editChapter);
});


// btn chapter
document.body.querySelector(".btn-create-chapter")?.addEventListener('click', openNewChapter);

// popup create chapter
$createChapterPopup?.querySelector(".btn-cancel")?.addEventListener('click', closeCreateChapter);
$createChapterPopup?.querySelector(".btn-confirm")?.addEventListener('click', createChapter);

// popup delete chapter
$deleteChapterPopup?.querySelector('.btn-cancel')?.addEventListener('click', closeDeleteChapter);
$deleteChapterPopup?.querySelector('.btn-confirm')?.addEventListener('click', confirmDeleteChapter);

// popup create question
$createQuestionPopup?.querySelector('.btn-cancel')?.addEventListener('click', closeCreateQuestion);
$createQuestionPopup?.querySelector('.btn-confirm')?.addEventListener('click', confirmCreateQuestion);

// popup delete question
$deleteQuestionPopup?.querySelector('.btn-cancel')?.addEventListener('click', closeDeleteQuestion);
$deleteQuestionPopup?.querySelector('.btn-confirm')?.addEventListener('click', confirmDeleteQuestion);


$formNewChapter?.addEventListener('submit', createChapter);
$formQuestion?.addEventListener('submit', createOrEditQuestion);

$formQuestion?.querySelector('.btn-add-answer')?.addEventListener('click', addProposal);



// ----------------------------------------------------
// popups (open/close)


function openDeleteChapter()
{
	$deleteChapterPopup.classList.remove("hidden");
	delete $deleteChapterPopup.dataset.id;
}

function closeDeleteChapter()
{
	$deleteChapterPopup.classList.add("hidden");
}

function openCreateQuestion()
{
	$createQuestionPopup.classList.remove('hidden');
}

function closeCreateQuestion()
{
	$createQuestionPopup.classList.add('hidden');
	
	$questionType?.removeEventListener('changed', selectQuestionType);

	$questionType.enable();
	
	delete $deleteQuestionPopup.dataset.idQuestion;
	delete $deleteQuestionPopup.dataset.idChapter;
}


function openDeleteQuestion()
{
	$deleteQuestionPopup.classList.remove('hidden');
}

function closeDeleteQuestion()
{
	$deleteQuestionPopup.classList.add('hidden');
	delete $deleteQuestionPopup.dataset.idQuestion;
	delete $deleteQuestionPopup.dataset.idChapter;
}

function openNewChapter()
{
	$createChapterPopup.classList.remove("hidden");
}

function closeCreateChapter()
{
	$createChapterPopup.classList.add("hidden");
	$createChapterPopup.querySelector("#chapterName").value = "";
	delete $createChapterPopup.dataset.id;
}







// ---------------------------------------------------
// intermediate callback function for opening popups

function deleteChapter()
{
	const chapterId = this.dataset.id;
	$deleteChapterPopup.dataset.id = chapterId;
	openDeleteChapter();
}

function deleteQuestion()
{
	const questionId = this.closest('tr')?.dataset.questionId || null;
	const chapterId = this.closest('details')?.dataset.id || null;
	$deleteQuestionPopup.dataset.idQuestion = questionId;
	$deleteQuestionPopup.dataset.idChapter = chapterId;
	openDeleteQuestion();
}

function editChapter()
{
	const chapterId = this.dataset.id;
	$createChapterPopup.dataset.id = chapterId;
	$createChapterPopup.querySelector("#chapterName").value = this.previousElementSibling.textContent;
	openNewChapter();
}

function editQuestion()
{
	const questionId = +(this.closest('tr')?.dataset.questionId || null);
	const chapterId = +(this.closest('details')?.dataset.id || null);

	$createQuestionPopup.dataset.idQuestion = questionId;
	$createQuestionPopup.dataset.idChapter = chapterId;

	$createQuestionPopup.classList.remove('no-type');

	const question = chapters[chapterId].find(q => q.id === +questionId);

	const $inner = $questionProps.querySelector('.inner');

	$questionType.select(question.type + '');
	$questionType.disable();

	$questionState.querySelector('#question-state').value = question.state;
	$questionState.classList.remove('hidden');

	$questionAns.querySelector('textarea').value = '';
	$inner.innerHTML = '';

	switch(question.type) {
		case 0:
			$questionProps.classList.add('hidden');
			$questionAns.classList.remove('hidden');
			$questionAns.querySelector('textarea').value = question.answers;
			$formQuestion.classList.add('vertical');
			break;

		case 1:
			$questionProps.classList.remove('hidden');
			$questionAns.classList.add('hidden');
			$formQuestion.classList.remove('vertical');

			for(let i in question.proposals) {
				const proposal = question.proposals[i];
				const $item = createPropositionItem(i, 'radio', proposal, question.answers === +i);
				$inner.appendChild($item);
			}
			break;

		case 2:
			$questionProps.classList.remove('hidden');
			$questionAns.classList.add('hidden');
			$formQuestion.classList.remove('vertical');

			for(let i in question.proposals) {
				const proposal = question.proposals[i];
				const $item = createPropositionItem(i, 'checkbox', proposal, question.answers.includes(+i));
				$inner.appendChild($item);
			}
			break;
	}

	$createQuestionPopup.querySelector('.actions .btn-confirm')?.removeAttribute('disabled');

	openCreateQuestion();
}

function createQuestion()
{
	const chapterId = this.closest('details')?.dataset.id || null;

	$createQuestionPopup.dataset.idChapter = chapterId;

	$createQuestionPopup.classList.add('no-type');
	
	$questionType?.reset();
	$questionType?.addEventListener('changed', selectQuestionType);

	$questionState.querySelector('#question-state').value = '';
	$questionState.classList.add('hidden');

	$questionProps.querySelector('.inner').innerHTML = '';
	$questionProps.classList.add('hidden');

	$questionAns.querySelector('textarea').value = '';
	$questionAns.classList.add('hidden');

	$createQuestionPopup.querySelector('.actions .btn-confirm')?.setAttribute('disabled', true);

	openCreateQuestion();
}





// -------------------------------------------------------
// confirmation (create/delete)


/* ========== CHAPTER ========== */

async function selectChapter() {
	if(!this.open) {
		return;
	}

	const chapterId = this.dataset.id;

	if(chapterId in chapters) {
		return;
	}

	try {
		const questions = await GET(`/api/courses/${courseId}/chapters/${chapterId}/questions`);

		chapters[chapterId] = questions;

		const $table = this.querySelector('table tbody');
		
		if($table) {
			$table.innerHTML = '';
		}

		for(const question of questions) {
			createQuestionHTML($table, question);
		}
	}
	catch(e) {
		createToast("Une erreur est survernue lors de la récupération des questions.", false, 2000);
		console.error(e);
	}
}

async function confirmDeleteChapter()
{
	const chapterId = $deleteChapterPopup.dataset.id;

	if (chapterId != NaN)
	{
		try
		{
			await DELETE(`/api/courses/${courseId}/chapters/${chapterId}`);

			document.getElementById("chapter-"+chapterId)?.remove();
			createToast("Le chapitre a bien été supprimé.", true, 2000);
		}
		catch(e)
		{
			createToast("Une erreur est survernue lors de la suppression du chapitre.", false, 2000);
			console.error(e);
		}
	}
	else
	{
		createToast("Une erreur est survernue lors de la suppression du chapitre.", false, 2000);
		console.error("Erreur: chapterId is NaN.");
	}

	closeDeleteChapter();
}

async function confirmFormChapter(chapterName)
{
	const idx = page.url.lastIndexOf('/');
	const courseId = page.url.substring(idx+1);
	const chapterId = $createChapterPopup.dataset.id;

	if (!chapterId)
	{
		try
		{
			const data = await PUT(`/api/courses/${courseId}/chapters`, {name: chapterName});
			
			const chapterId = data["chapterId"];

			const $chapter = document.createElement('details');
			$chapter.id = `chapter-${chapterId}`;

			$chapter.dataset.id = chapterId;
			$chapter.innerHTML = `<summary>
					<h2>
						<span>${chapterName}</span>
						<button class="btn-edit text nohref" data-id=${chapterId}></button>
						<button class="btn-delete nohref text" data-id=${chapterId}></button>
					</h2>
				</summary>
				
				<table>
					<thead>
						<tr>
							<th class="q-num">N°</th>
							<th class="q-type">type</th>
							<th class="q-state">énoncé</th>
							<th class="q-props">propositions</th>
							<th class="q-ans">réponses</th>
							<th class="q-creation">date de création</th>
							<th class="q-actions"></th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
				<div class="actions">
					<button class="nohref primary btn-create-question" data-id=${chapterId}>+ Créer</button>
				</div>`;

			$chapter.querySelector('.btn-edit')?.addEventListener('click', editChapter);
			$chapter.querySelector('.btn-delete')?.addEventListener('click', deleteChapter);
			$chapter.querySelector('.btn-create-question')?.addEventListener('click', createQuestion);

			$article.appendChild($chapter);

			createToast("Le chapitre a bien été crée.", true, 2000);
		}
		catch(e)
		{
			createToast("Une erreur est survernue lors de la création du chapitre.", false, 2000);
			console.error(e);
		}
	}
	else
	{
		try
		{
			await POST(`/api/courses/${courseId}/chapters/${chapterId}`, {name: chapterName});

			const $span = document.getElementById("chapter-"+chapterId).getElementsByTagName("span");
			$span[0].textContent = chapterName;
			createToast("Le chapitre a bien été modifié.", true, 2000);
		}
		catch(e)
		{
			createToast("Une erreur est survernue lors de la modification du chapitre.", false, 2000);
			console.error(e);
		}
	}

	closeCreateChapter();
}

function createChapter(e) {
	e.preventDefault();

	const data = new FormData($formNewChapter);
	confirmFormChapter(data.get("chapterName"));
}



/* ========== QUESTION ========== */


async function confirmCreateQuestion()
{
	function getReason() {
		switch(reason) {
			case 1:
				return "Énoncé manquant";
			case 2:
				return "Réponse manquante";
			case 3:
				return "Des propositions sont vides";
			case 4:
				return "Il faut au moins une bonne réponse";
			case 5:
				return "Des propositions sont identiques";
			default:
				return '';
		}
	}

	let isValid = true;
	let reason = 0;

	const type = +$questionType.dataset.value;

	const iptType = (type === 1)? 'radio' : 'checkbox';

	const state = $questionState.querySelector('textarea').value.trim();
	let answers = $questionAns.querySelector('textarea').value.trim(); // text
	let proposals = [...$questionProps.querySelectorAll('.proposition')].map($p => ({
		value: $p.querySelector('input[type="text"]')?.value.trim() || '',
		selected: $p.querySelector(`input[type="${iptType}"]`)?.checked || false
	}));

	if(state.length < 10) {
		isValid = false;
		reason = 1;
	}

	switch(type) {
		case 0:
			if(answers.length === 0) {
				isValid = false;
				reason = 2;
			}
			
			proposals = '';
			break;
		case 1:
		case 2:
			const emptyProps = proposals.filter(p => p.value.length === 0);
			const checkedProps = proposals.filter(p => p.selected);

			if(emptyProps.length > 0) {
				isValid = false;
				reason = 3;
			}

			else if(checkedProps.length === 0) {
				isValid = false;
				reason = 4;
			}

			else if((new Set(proposals.map(p => p.value)).size) !== proposals.length) {
				isValid = false;
				reason = 5;
			}

			answers = (type === 1)
				? proposals.findIndex(p => p.selected) + ''
				: proposals.map((p, i) => p.selected? i : -1).filter(i => i > -1).join(';');
			proposals = proposals.map(p => p.value).join(';');
			break;
	}

	if(!isValid) {
		createToast(getReason(), false, 3000);
		return;
	}

	const question = {
		type: type + '',
		state,
		proposals,
		answers
	};

	if('idQuestion' in $createQuestionPopup.dataset) {
		return confirmEditQuestion(question);
	}

	try {
		const chapterId = $createQuestionPopup.dataset.idChapter;
		const questionId = await PUT(`/api/courses/${courseId}/chapters/${chapterId}/questions`, question);

		question.id = questionId;
		question.type = +question.type;
		question.createdAt = Date.now();

		const $table = $article.querySelector(`details[data-id="${chapterId}"] table tbody`);

		switch(question.type) {
			case 2:
				question.answers = question.answers.split(';').map(e => +e);
			case 1:
				question.proposals = question.proposals.split(';');
				break;
		}

		chapters[chapterId].push(question);

		createQuestionHTML($table, question);

		createToast('Question créée', true, 2000);
		closeCreateQuestion();
	}
	catch(e) {
		console.error(e);
		createToast('Une erreur est survenue', false, 2000);
	}
}

/**
 * 
 * @param {{type: number, state: string, answer: string, proposals: string}} question 
 */
async function confirmEditQuestion(question) {
	const type = question.type;

	if(type === 0 && 'proposals' in question) {
		delete question.proposals;
	}

	delete question.type;

	try {
		const chapterId = +$createQuestionPopup.dataset.idChapter;
		const questionId = +$createQuestionPopup.dataset.idQuestion;

		await POST(`/api/courses/${courseId}/chapters/${chapterId}/questions/${questionId}`, question);

		const oQ = chapters[chapterId].find(q => q.id === questionId);

		if(oQ) {
			oQ.state = question.state;
			oQ.answers = (type === 2)? question.answers : question.answers.split(';').map(e => +e+1);
			oQ.proposals = (type === 0)? '' : question.proposals.split(';');
		}

		const $tr = $article.querySelector(`details[data-id="${chapterId}"] table tr[data-question-id="${questionId}"]`);

		if($tr) { // TODO : pas parfait
			$tr.querySelector('.q-state').textContent = question.state;
			$tr.querySelector('.q-props').textContent = question.proposals.replace(/;/g, ', ');
			$tr.querySelector('.q-ans').textContent = question.answers.replace(/;/g, ', ');
		}

		createToast('Question modifiée', true, 2000);
		closeCreateQuestion();
	}
	catch(e) {
		console.error(e);
		createToast('Une erreur est survenue', false, 2000);
	}
}


async function confirmDeleteQuestion()
{
	try {
		const questionId = $deleteQuestionPopup.dataset.idQuestion;
		const chapterId = $deleteQuestionPopup.dataset.idChapter;

		await DELETE(`/api/courses/${courseId}/chapters/${chapterId}/questions/${questionId}`);

		document.querySelector(`details[data-id="${chapterId}"] tr[data-question-id="${questionId}"]`)?.remove();

		createToast('Question supprimée', true, 2000);
	}
	catch(e) {
		console.error(e);
		createToast('Une erreur est survenue', false, 2000);
	}
	closeDeleteQuestion();
}


function selectQuestionType() {
	$createQuestionPopup.classList.remove('no-type');
	$questionState.classList.remove('hidden');
	$createQuestionPopup.querySelector('.actions .btn-confirm').removeAttribute('disabled');

	switch(this.dataset.value) {
		case '0':
			$formQuestion.classList.add('vertical');
			$questionProps.classList.add('hidden');
			$questionAns.classList.remove('hidden');
			break;
		case '1':
			$formQuestion.classList.remove('vertical');
			$questionAns.classList.add('hidden');
			$questionProps.classList.remove('hidden');
			setupQuestionProps('radio');
			break;
		case '2':
			$formQuestion.classList.remove('vertical');
			$questionAns.classList.add('hidden');
			$questionProps.classList.remove('hidden');
			setupQuestionProps('checkbox');
			break;
	}
}

function createOrEditQuestion(e) {
	e.preventDefault();
}


function addProposal() {
	const $inner = $formQuestion.querySelector('.inner');

	const propsCount = $inner.children.length;
	const propsType = (+$questionType.dataset.value === 1)? 'radio' : 'checkbox';
	
	const $item = createPropositionItem(propsCount, propsType);
	
	$inner.appendChild($item);
}

/**
 * 
 * @param {'radio'|'checkbox'} type 
 */
function setupQuestionProps(type) {
	const $list = $questionProps.querySelector('.inner');

	if($list.children.length === 0) {
		const $prop1 = createPropositionItem(0, type);
		const $prop2 = createPropositionItem(1, type);

		$list.append($prop1, $prop2);
	}
	else {
		for(let i=0; i < $list.children.length; i++) {
			const $prop = $list.children.item(i);
			$prop.children.item(1).type = type;
			$prop.children.item(1).checked = false;
		}
	}
}

/**
 * @param {number} idx
 * @param {'radio'|'checkbox'} type 
 * @param {any} value
 * @param {boolean} selected
 * @returns 
 */
function createPropositionItem(idx, type, value=undefined, selected=false) {
	const $emptyProps = document.createElement('div');
	$emptyProps.classList.add('proposition');
	
	const $input = document.createElement('input');
	$input.type = 'text';
	$input.placeholder = 'proposition 1';

	if(value) {
		$input.value = value;
	}
	
	const $isAnsIpt = document.createElement('input');
	$isAnsIpt.type = type;
	$isAnsIpt.classList.add('ipt-is-answer');

	if(selected) {
		$isAnsIpt.checked = true;
	}

	if(type === 'radio') {
		$isAnsIpt.name = 'ipt-proposal';
	}

	const $dltBtn = document.createElement('button');
	$dltBtn.classList.add('btn-delete-props', 'text');

	if(idx < 2) {
		$dltBtn.setAttribute('disabled', true);
		$dltBtn.setAttribute('tabindex', -1);
	}
	else {
		$dltBtn.addEventListener('click', () => {
			$emptyProps.remove();
		});
	}
	
	$emptyProps.append($input, $isAnsIpt, $dltBtn);

	return $emptyProps;
}



/**
 * @param {HTMLDetailsElement}
 * @param {Question} question
 */
function createQuestionHTML($table, question)
{	
	if(!$table) {
		return;
	}

	const count = $table.children.length + 1;

	const $tr = document.createElement('tr');
	$tr.dataset.questionId = question.id;


	let sProps = question.proposals,
		sAns = question.answers;

	switch(question.type) {
		case 0:
			break;
		case 1: // SINGLE
			sProps = question.proposals.join(', ');
			sAns = +question.answers + 1;
			break;
		case 2: // MULTIPLE
			sProps = question.proposals.join(', ');
			sAns = question.answers.map(i => +i+1).join(', ');
			break;
	}

	const maxChar = 40;
	const state = question.state.length > maxChar ? question.state.substring(0, maxChar) + '...' : question.state;
	const props = sProps.length > maxChar ? sProps.substring(0, maxChar) + '...' : sProps;
	const ans = sAns.length > maxChar ? sAns.substring(0, maxChar) + '...' : sAns;
	const type = getQuestionType(question.type);

	const date = new Date(question.createdAt);

	const d  = ('0' + date.getDay()).slice(-2);
	const mo = ('0' + date.getMonth()).slice(-2);
	const y  = (''  + date.getFullYear()).slice(-2);

	const h  = ('0' + date.getHours()).slice(-2);
	const mi = ('0' + date.getMinutes()).slice(-2);
	const s  = ('0' + date.getSeconds()).slice(-2);

	const fdate = `${d}/${mo}/${y} ${h}:${mi}:${s}`;


	const $editBtn = document.createElement('button');
	$editBtn.classList.add('btn-edit', 'text', 'nohref');

	const $deleteBtn = document.createElement('button');
	$deleteBtn.classList.add('btn-delete', 'text', 'nohref');

	$editBtn.addEventListener('click', editQuestion);
	$deleteBtn.addEventListener('click', deleteQuestion);


	$tr.innerHTML = `<td class="q-num">${count}</td>
	<td class="q-type">${type}</td>
	<td class="q-state">
		<div>
			${state}
		</div>
	</td>
	<td class="q-props">
		<div>
			${props}
		</div>
	</td>
	<td class="q-ans">${ans}</td>
	<td class="q-creation">${fdate}</td>
	<td class="q-actions"></td>`;

	$tr.querySelector('.q-actions')?.append($editBtn, $deleteBtn);

	$table.appendChild($tr);
}







// --------------------------------------------
// utils

function getQuestionType(type)
{
	switch (type)
	{
		case 0:
			return "Texte";
		case 1:
			return "Choix unique";
		case 2:
			return "Choix multiple";
		default:
			return 'INCONNU';
	}
}