/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

import { $table, updatePagination, $filterBar, $newBtn, $delBtn, $editBtn, selectedRows, resetSelectedRows, $tbody } from './adminBoardListManager';
import { DELETE, GET, POST, PUT } from '../../ajax';
import { createToast, page, updateUrl } from '../../script';



function getUserById(id) {
	return users.data[id];
}

function hasUserRole(id, role) {
	return users.roles[role]?.data.findIndex(uid => uid === id) > -1 || false;
}


/**
 * 
 * @param {string} role 
 * @returns 
 */
async function fetchTable(role) {
	currentRole = role;
	
	if(!(role in users.roles)) {
		try {
			const data = await GET('/api/users/byRole/' + role, 'json');

			users.roles[role] = {
				'total': data.length, // to change once pagination is done
				'page': 1, // to do once pagination is done
				'maxPage': 1, // to do once pagination is done
				'data': [],
				'size': data.length
			};

			for(const user of data) {
				users.roles[role].data.push(user.userId);

				if(!(user.userId in users.data)) {
					const present = user.userId in users.data;
					
					users.data[user.userId] = user;

					if(!present) {
						users.data[user.userId].incomplete = true;
					}
				}
			}

			updateUsersTable(role);

			for(const userId of users.roles[role].data) {
				if(users.data[userId].incomplete === undefined) {
					return;
				}

				const userDetails = await GET(`/api/users/byId/${userId}`);

				delete users.data[userId].incomplete;
		
				if(userDetails) {
					const user = users.data[userId];

					user.firstname = userDetails.firstname;
					user.lastname = userDetails.lastname;
					user.role = 0;

					if(userDetails.studentId !== null) {
						user.role |= $isStudent.value;
					}

					if(userDetails.teacherId !== null) {
						user.role |= $isTeacher.value;
					}

					if('level' in userDetails) {
						user.cursusId = userDetails.level.cursusId;
						user.levelId = userDetails.level.levelId;
						user.yearId = userDetails.level.yearId;
						user.year = userDetails.level.yearName;
					}

					updateRow(user);
				}
			}
		}
		catch(e) {
			console.error(e);
			createToast('Une erreur est survenue lors du rafraîchissement des utilisateurs', false, 2000);
		}
	}
	else {
		updateUsersTable(role);
	}
}

/**
 * 
 * @param {string} role 
 */
function updateUsersTable(role) {
	if(!$tbody) {
		return;
	}

	$tbody.innerHTML = '';

	for(const userId of users.roles[role].data) {
		const user = getUserById(userId);

		if(!user) {
			continue;
		}

		addRow(user);
	}

	updatePagination(users.roles[role]);
}

function addRow(user) {
	const $tr = `<tr data-userId="${user.userId}">
		<td class="select-box">
			<div>
				<input type="checkbox" name="row" value="${user.userId}"/>
			</div>
		</td>
		<td class="user-uuid">${user.userUUID??''}</td>
		<td class="user-firstname">${user.firstname??''}</td>
		<td class="user-lastname">${user.lastname??''}</td>
		<td class="user-mail">${user.userMail??''}</td>
	</tr>`;

	$tbody.innerHTML += $tr;
}

function updateRow(user) {
	const $tr = $tbody?.querySelector(`tr[data-userId="${user.userId}"]`);

	if($tr) {
		$tr.querySelector('.user-uuid').innerText = user.userUUID;
		$tr.querySelector('.user-firstname').innerText = user.firstname;
		$tr.querySelector('.user-lastname').innerText = user.lastname;
		$tr.querySelector('.user-mail').innerText = user.userMail;
	}
}

function deleteRow(userId) {
	$tbody?.querySelector(`tr[data-userId="${userId}"]`)?.remove();
}

function addUser(data) {
	const user = {
		firstname: data.firstname,
		lastname: data.lastname,
		userMail: data.mail,
		userId: data.userId,
		userUUID: data.uuid,
		role: data.role,
		cursusId: data.cursusId,
		levelId: data.levelId,
		year: data.year
	};

	users.data[user.userId] = user;

	const roles = [
		[$isStudent?.value || -1, $isStudent?.dataset.value],
		[$isTeacher?.value || -1, $isTeacher?.dataset.value]
	];

	for(const _ of roles) {
		const roleValue = +_[0];
		const roleName = _[1];
		const role = users.roles[roleName];
		
		if(!role) {
			continue;
		}

		if((data.role & roleValue) === roleValue) {
			role.data.push(user.userId);
			role.size++;
			role.total++;
			updatePagination(role);

			if(roleName === currentRole) {
				addRow(user);
			}
		}
	}
}

function updateUser(userId, data) {
	if(!(userId in users.data)) {
		return addUser(data);
	}

	const user = users.data[userId];

	const $tr = $table?.querySelector(`tr[data-userId="${userId}"]`);

	if(!$tr) {
		return;
	}

	if('mail' in data) {
		user.userMail = data.mail;
		$tr.querySelector('.user-mail').innerText = data.mail;
	}

	if('firstname' in data) {
		user.firstname = data.firstname;
		$tr.querySelector('.user-firstname').innerText = data.firstname;
	}

	if('lastname' in data) {
		user.lastname = data.lastname;
		$tr.querySelector('.user-lastname').innerText = data.lastname;
	}

	if('role' in data) {
		const roles = [
			[$isStudent?.value || -1, $isStudent?.dataset.value],
			[$isTeacher?.value || -1, $isTeacher?.dataset.value]
		];

		for(const _ of roles) {
			const roleValue = +_[0];
			const roleName = _[1];
			const role = users.roles[roleName];
			
			if(!role) {
				continue;
			}

			const idx = role.data.indexOf(userId);

			if((data.role & roleValue) === roleValue) {
				if(idx === -1) {
					users.roles[roleName].data.push(userId);
					users.roles[roleName].size++;
					users.roles[roleName].total++;

					if(roleName === currentRole) {
						addRow(user);
					}
				}
			}
			else if(idx > -1) {
				users.roles[roleName].data.splice(idx, 1);
				users.roles[roleName].size--;
				users.roles[roleName].total--;

				if(roleName === currentRole) {
					deleteRow(userId);
				}
			}

			updatePagination(users.roles[roleName]);
		}

		if((data.role & +$isStudent.value) === +$isStudent.value && data.year) {
			user.year = data.year;
			user.levelId = data.levelId;
			user.cursusId = data.cursusId;
		}
	}
}

function deleteUser(userId) {
	if(userId in users.data) {
		delete users.data[userId];
	}

	for(const role in users.roles) {
		const idx = users.roles[role].data.indexOf(userId);

		if(idx > -1) {
			users.roles[role].data.splice(idx, 1);
			users.roles[role].size--;
			users.roles[role].total--;

			updatePagination(users.roles[role]);
		}
	}

	deleteRow(userId);
}

async function createUser() {
	const fdata = new FormData($creationPopup.querySelector('form'));

	const data = {
		mail: 		fdata.get('crt-usr-mail').trim(),
		password: 	fdata.get('crt-usr-pass').trim(),
		firstname: 	fdata.get('crt-usr-frst').trim(),
		lastname: 	fdata.get('crt-usr-lst').trim(),
		uuid: 		fdata.get('crt-usr-uuid').trim(),
		role: 		+fdata.get('crt-usr-role-st') |
					+fdata.get('crt-usr-role-te')
	}

	for(const f in data) {
		if(typeof f === 'string' && data[f].length === 0) {
			createToast('Champs incomplets', false, 2000);
			return;
		}
	}

	if(!/^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/.test(data.mail)) {
		createToast('Format de mail inconnu', false, 2000);
		return;
	}

	if((data.role & +$isStudent.value) === +$isStudent.value) {
		const year = +$yearSLCT.dataset.value;
		const cursus = +$cursusSLCT.dataset.value;
		const level = +$levelSLCT.dataset.value;

		if(year && cursus && level) {
			data.year = year;
			data.levelId = level;
			data.cursusId = cursus;
		}
	}

	try {
		const { userId } = await PUT('/api/users', data);

		data.userId = +userId;

		addUser(data);

		$creationPopup.classList.add('hidden');
		createToast('Nouvel utilisateur créé', true, 2000);
	}
	catch(e) {
		createToast('Une erreur est survenue', false, 2000);
	}
}

async function editUser() {
	const fdata = new FormData($creationPopup.querySelector('form'));

	const data = {
		mail: 		fdata.get('crt-usr-mail').trim(),
		firstname: 	fdata.get('crt-usr-frst').trim(),
		lastname: 	fdata.get('crt-usr-lst').trim(),
		role: 		+fdata.get('crt-usr-role-st') |
					+fdata.get('crt-usr-role-te')
	}

	for(const f in data) {
		if(
			(typeof f === 'string' && data[f].length === 0)
			|| data[f] === 0
			|| userEditing[f] === data[f] // value didn't changed
		) {
			delete data[f];
		}
	}

	if('mail' in data && !/^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/.test(data.mail)) {
		createToast('Format de mail inconnu', false, 2000);
		return;
	}

	if((data.role & +$isStudent.value) === +$isStudent.value) {
		const year = +$yearSLCT.dataset.value;
		const cursus = +$cursusSLCT.dataset.value;
		const level = +$levelSLCT.dataset.value;

		if(year && cursus && level) {
			data.year = year;
			data.levelId = level;
			data.cursusId = cursus;
		}
	}

	try {
		await POST(`/api/users/${userEditing.userId}`, data);

		updateUser(userEditing.userId, data);

		$creationPopup.classList.add('hidden');
		createToast('Utilisateur modifié', true, 2000);

	}
	catch(e) {
		console.error(e);
		createToast('Une erreur est survenue', false, 2000);
	}
}

function changeRole() {
	const $ipt = $table.querySelector('thead tr input[type="checkbox"]');

	if($ipt?.checked) {
		$ipt.click();
	}

	const role = $switchRole.dataset.value;

	page.query.role = role;

	if(role !== $isStudent.dataset.value) {
		$yearFLT.disable();
		$cursusFLT.disable();
		$levelFLT.disable();
	}
	else {
		$yearFLT.enable();
		$cursusFLT.enable();
		$levelFLT.enable();
	}

	updateUrl();

	fetchTable(role);
}


// ------------------------- OPEN POPUPS -------------------------

function openCreation() {
	if($creationPopup) {
		$creationPopup.dataset.action = "create";

		// reset fields
		$creationPopup.querySelectorAll('input[type="text"], input[type="email"]').forEach($ipt => {
			$ipt.value = '';
			$ipt.removeAttribute('disabled');
		})

		$creationPopup.querySelectorAll('input[type="checkbox"]').forEach($ipt => {
			$ipt.checked = false;
		})

		$creationPopup.querySelector('.student-only').classList.add('hidden');

		resetCreationStudentSelect();

		$creationPopup.classList.remove('hidden');
	}
}

function openEdition() {
	if($creationPopup) {
		$creationPopup.dataset.action = "edit";

		// fill fields
		const userId = +Object.keys(selectedRows)[0];
		const user = getUserById(userId);
		
		if(!user) {
			return;
		}

		userEditing = user;

		$creationPopup.dataset.userId = userId;

		$creationPopup.querySelector('.student-only').classList.add('hidden');

		resetCreationStudentSelect();

		const match = {
			'userUUID': 	['create-user-uuid', true, 'uuid'],
			'firstname': 	['create-user-firstname', false, 'firstname'],
			'lastname': 	['create-user-lastname', false, 'lastname'],
			'userMail': 	['create-user-mail', false, 'mail']
		};

		for(const key in match) {
			const $field = $creationPopup.querySelector(`form #${match[key][0]}`);

			if($field) {
				$field.value = user[key];

				if(match[key][1]) {
					$field.setAttribute('disabled', true);
				}
				else {
					$field.removeAttribute('disabled');
				}
			}
		}

		const $pass = $creationPopup.querySelector('form #create-user-pass');

		if($pass) {
			$pass.value = "******";
			$pass.setAttribute('disabled', true);
		}
		
		if($isStudent) {
			$isStudent.checked = hasUserRole(userId, $isStudent.dataset.value);

			if($isStudent.checked) {
				$creationPopup.querySelector('.student-only').classList.remove('hidden');

				if(user.year !== null) {
					$yearSLCT.select(user.year);
				}

				if(user.cursusId !== null) {
					$cursusSLCT.select(user.cursusId);
					
					if(user.levelId !== null) {
						$levelSLCT.select(user.levelId);
					}
				}
			}
		}

		if($isTeacher) {
			$isTeacher.checked = hasUserRole(userId, $isTeacher.dataset.value);
		}


		$creationPopup.classList.remove('hidden');
	}
}

function openDeletion() {
	$deletePopup?.classList.remove('hidden');
}



// ------------------------- CANCEL -------------------------

function cancelCreationOrEdition() {
	$creationPopup.classList.add('hidden');
}

function cancelDeletion() {
	$deletePopup.classList.add('hidden');
}



// ------------------------- CONFIRM -------------------------

function confirmCreationOrEdition() {
	const action = $creationPopup.dataset.action;

	if(action === 'create')
		createUser();
	else
		editUser();
}

async function confirmDeletion() {
	const ids = Object.keys(selectedRows);
	const reqs = ids
		.map(v => DELETE(`/api/users/${v}`))

	try {
		const res = (await Promise.allSettled(reqs));
		const rej = res.filter((r, i) => {
			const keep = r.status === 'rejected';

			if(!keep) { // fullfilled
				const userId = +ids[i];
				deleteUser(userId);
			}

			return keep;
		});
		
		if(rej.length > 0) {
			throw new Error();
		}

		let msg = 'Utilisateur supprimé';

		if(ids.length > 1) {
			msg = 'Utilisateurs supprimés';
		}

		createToast(msg, true, 2000);
		$deletePopup.classList.add('hidden');
	}
	catch(e) {
		console.error(e);
		createToast('Une erreur est survenue pour la suppression de certains utilisateurs', false, 2000);
	}

	resetSelectedRows();
	$editBtn?.setAttribute('disabled', true);
	$delBtn?.setAttribute('disabled', true);
}



// ------------------------- FILTERS -------------------------

function applyFilters() {
	const cond = user => (
		(filters.years === null || (user.year && filters.years.includes(user.year))) &&
		(filters.cursus === null || (user.cursusId && filters.cursus.includes(user.cursusId))) &&
		(filters.levels === null || (user.levelId && filters.levels.includes(user.levelId)))
	);

	const l = $tbody.children.length;

	for(let i=0; i < l; i++) {
		const $tr = $tbody.children.item(i);

		const userId = $tr.dataset.userid;
		const user = users.data[userId];

		if(!user || cond(user)) {
			$tr.classList.remove('hidden');
		}
		else {
			$tr.classList.add('hidden');
		}
	}
}

function filterYear() {
	if(!$yearFLT || !$yearFLT.dataset.value)
		return;

	if($yearFLT.dataset.value === '*') {
		filters.years = null;
	}
	else {
		filters.years = $yearFLT.dataset.value.split(';').map(v => +v);
	}

	applyFilters();
}

function filterCursus() {
	if(!$cursusFLT || !$cursusFLT.dataset.value)
		return;

	if($cursusFLT.dataset.value === '*') {
		$levelOpts.forEach($opt => {
			if('cursus' in $opt.dataset) {
				$opt.classList.add('hidden');
			}
		});

		filters.cursus = null;
		filters.levels = null;
	}
	else {
		const values = $cursusFLT.dataset.value.split(';');

		$levelOpts.forEach($opt => {
			if('cursus' in $opt.dataset) {
				if(values.includes($opt.dataset.cursus)) {
					$opt.classList.remove('hidden');
				}
				else {
					$opt.classList.add('hidden');
				}
			}
		});

		filters.cursus = values.map(v => +v);
	}

	applyFilters();
}

function filterLevel() {
	if(!$levelFLT || !$levelFLT.dataset.value)
		return;

	if($levelFLT.dataset.value === '*') {
		filters.levels = null;
	}
	else {
		filters.levels = $levelFLT.dataset.value.split(';').map(v => +v);
	}

	applyFilters();
}



function toggleStudentForm() {
	if(this.checked)
		$creationPopup.querySelector('.student-only')?.classList.remove('hidden');
	else {
		$creationPopup.querySelector('.student-only')?.classList.add('hidden');
		resetCreationStudentSelect();
	}
}

function resetCreationStudentSelect() {
	$yearSLCT?.reset();
	$cursusSLCT?.reset();
	$levelSLCT?.reset();

	$levelSLCT?.querySelectorAll('opt').forEach($opt => $opt.classList.add('hidden'));
}

function createStudentChangeCursus() {
	const cursusId = $cursusSLCT.dataset.value;
	
	$levelSLCT?.querySelectorAll('opt').forEach($opt => {
		if($opt.dataset.cursus === cursusId) {
			$opt.classList.remove('hidden');
		}
		else {
			$opt.classList.add('hidden');
		}
	})
}




// ------

const users = {
	roles: {},
	data: {}
};
let currentRole = '';
let userEditing = {};

const filters = {
	years: null,
	cursus: null,
	levels: null
};

const $switchRole = $filterBar?.querySelector('#switch-user-type');

const $creationPopup = document.querySelector('.creation-popup');
const $deletePopup = document.querySelector('.delete-popup');

const $yearFLT = $filterBar.querySelector('#slct-year');
const $cursusFLT = $filterBar.querySelector('#slct-cursus');
const $levelFLT = $filterBar.querySelector('#slct-level');

const $levelOpts = $levelFLT?.querySelectorAll('opt');

const $yearSLCT = $creationPopup?.querySelector('#crt-slct-year');
const $cursusSLCT = $creationPopup?.querySelector('#crt-slct-cursus');
const $levelSLCT = $creationPopup?.querySelector('#crt-slct-level');

const $isStudent = $creationPopup?.querySelector('form #create-user-role-student');
const $isTeacher = $creationPopup?.querySelector('form #create-user-role-teacher');


$newBtn?.addEventListener('click', openCreation);
$editBtn?.addEventListener('click', openEdition);
$delBtn?.addEventListener('click', openDeletion);

$creationPopup?.querySelector('.btn-cancel')?.addEventListener('click', cancelCreationOrEdition);
$creationPopup?.querySelector('.btn-confirm')?.addEventListener('click', confirmCreationOrEdition);

$deletePopup?.querySelector('.btn-cancel')?.addEventListener('click', cancelDeletion);
$deletePopup?.querySelector('.btn-confirm')?.addEventListener('click', confirmDeletion);

$yearFLT?.addEventListener('changed', filterYear);
$cursusFLT?.addEventListener('changed', filterCursus);
$levelFLT?.addEventListener('changed', filterLevel);

$creationPopup?.querySelector('#create-user-role-student').addEventListener('change', toggleStudentForm);

$cursusSLCT?.addEventListener('changed', createStudentChangeCursus);

if($switchRole) {
	$switchRole.addEventListener('valueChanged', changeRole);
}


document.addEventListener('DOMContentLoaded', () => {
	changeRole();
	filterCursus();
});