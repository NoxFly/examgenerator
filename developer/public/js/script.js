/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

import { GET, POST, PUT, DELETE, PATCH, OPTIONS, HEAD } from './ajax';

const baseRequestPath = '/api';

document.querySelectorAll('details').forEach($details => {
	$details.querySelector('summary').addEventListener('click', () => {
		const $opened = document.querySelector('details[open]');

		if($opened && $opened !== $details) {
			$opened.removeAttribute('open');
		}
	});
});

document.querySelectorAll('.endpoint').forEach($endpoint => {
	const $execBtn = $endpoint.querySelector('.exec-req');
	const $form = $endpoint.querySelector('form');
	const path = $endpoint.querySelector('summary .path')?.textContent;
	const $parameters = $form.querySelectorAll('.param-row.api-param');

	const $apiUri = $endpoint.querySelector('.api-uri .inner a');
	const $reqHdrs = $endpoint.querySelector('.request-headers .inner pre');
	const $resBdy = $endpoint.querySelector('.response-body .inner pre');

	const $rightInnerQuery = $endpoint.querySelector('.right-column .inner-query');
	const $rightInnerBody = $endpoint.querySelector('.right-column .inner-body');

	const $queryList = $rightInnerQuery?.querySelector('.query-list');
	const $bodyList = $rightInnerBody?.querySelector('.body-list');

	const $selectMethod = $form.querySelector('.param-row.api-method .select.method-tabs');

	const $responseWrapper = $endpoint.querySelector('.response-wrapper');

	const $reqTime = $endpoint.querySelector('.req-time');

	$selectMethod.addEventListener('changed', () => {
		switch($selectMethod.dataset.value) {
			case 'GET':
			case 'DELETE':
			case 'OPTIONS':
			case 'HEAD':
				$rightInnerQuery?.classList.remove('hidden');
				$rightInnerBody?.classList.add('hidden');
				break;

			case 'POST':
			case 'PUT':
			case 'PATCH':
				$rightInnerQuery?.classList.add('hidden');
				$rightInnerBody?.classList.remove('hidden');
		}
	});

	const paramListCB = ($parent, name) => {
		const $param = document.createElement('div');
		$param.classList.add('param-row', 'api-'+name);

		const $keyIpt = document.createElement('input');
		$keyIpt.setAttribute('type', 'text');
		$keyIpt.setAttribute('placeholder', 'key');
		$keyIpt.classList.add(name+'-key');

		const $valueIpt = document.createElement('input');
		$valueIpt.setAttribute('type', 'text');
		$valueIpt.setAttribute('placeholder', 'value');
		$valueIpt.classList.add(name+'-value');

		const $deleteBtn = document.createElement('button');
		$deleteBtn.classList.add('delete-param');

		$deleteBtn.addEventListener('click', () => {
			$param.remove();
		});

		$param.append($keyIpt, $valueIpt, $deleteBtn);

		$parent.append($param);
	};

	$rightInnerQuery.querySelector('.add-param')?.addEventListener('click', () => paramListCB($queryList, 'query'));
	$rightInnerBody.querySelector('.add-param')?.addEventListener('click', () => paramListCB($bodyList, 'body'));
	
	

	$form?.addEventListener('submit', async e => {
		e.preventDefault();

		if($execBtn.hasAttribute('disabled')) {
			return;
		}

		$execBtn.setAttribute('disabled', true);

		const obj = {
			method: ($selectMethod?.dataset.value?.trim()) || null,
			path: baseRequestPath + path,
			query: '',
			body: {},
			headers: {},
			token: $form.querySelector('.param-row.api-token input').value.trim(),
			tokenLocation: $form.querySelector('.token-location input:checked')?.value
		};


		$parameters.forEach($param => {
			const key = $param.querySelector('label .param-name').innerText.trim();
			const value = $param.querySelector('input').value;

			const reg = new RegExp(`{${key}}`);

			obj.path = obj.path.replace(reg, value);
		});

		switch(obj.method) {
			case 'GET':
			case 'DELETE':
			case 'OPTIONS':
			case 'HEAD':
				const queries = [...$queryList.querySelectorAll('.param-row.api-query')]
					.map(e => {
						const a = e.querySelector('.query-key')?.value.trim();
						const b = e.querySelector('.query-value')?.value.trim();
						return (a && b)? a + '=' + b : '';
					})
					.filter(e => !!e)
					.join('&');

				if(queries.length > 1) {
					obj.query = '?' + queries;
				}
				break;

			case 'POST':
			case 'PUT':
			case 'PATCH':
				$bodyList.querySelectorAll('.param-row.api-body').forEach($param => {
					const key = $param.querySelector('.body-key').value.trim();
					let value = $param.querySelector('.body-value').value;

					try {
						value = JSON.parse(value);
					}
					catch(e) {}
	
					if(key?.length > 0) {
						obj.body[key] = value;
					}
				});	
		}

		if(obj.token.length > 0) {
			if(obj.tokenLocation === 'query') {
				obj.path += '?api_key=' + obj.token;
			}
			else if(obj.tokenLocation === 'header') {
				obj.headers['X-Auth-Token'] = obj.token;
			}
		}

		let ajax = null;

		switch(obj.method) {
			case 'GET': 		ajax = GET; break;
			case 'POST': 		ajax = POST; break;
			case 'PUT': 		ajax = PUT; break;
			case 'DELETE': 		ajax = DELETE; break;
			case 'PATCH': 		ajax = PATCH; break;
			case 'OPTIONS': 	ajax = OPTIONS; break;
			case 'HEAD': 		ajax = HEAD; break;
		}

		if(ajax) {
			let res = {};

			const t0 = Date.now();
			let t1 = 0;
			
			try {
				switch(obj.method) {
					case 'GET':
					case 'DELETE':
					case 'OPTIONS':
					case 'HEAD':
						res = await ajax(obj.path + obj.query, 'json', 'json', obj.headers);
						break;

					case 'POST':
					case 'PUT':
					case 'PATCH':
						res = await ajax(obj.path + obj.query, obj.body, 'json', 'json', obj.headers);
						break;
				}

				t1 = Date.now();
			}
			catch(e) {
				t1 = Date.now();

				res = e;

				console.error(e);

				if(res.body && res.body.response && res.body.response.indexOf('<table class=') > -1) {
					const parser = new DOMParser();
					let html = parser.parseFromString(res.body.response, 'text/html').querySelector('table');
					let content = [];

					html.querySelectorAll('table tr').forEach(($tr, i) => {
						if(i === 0) {
							$tr.querySelector('span')?.remove();
							content.push($tr.innerText.trim().replace(/\\/g, '/'));
						}
						else if(i > 3) {
							content.push($tr.children.item(4).innerText.trim().replace(/\.+\\/g, ''));
						}
					});
					
					res.body.response = content;
				}
				else {
					try {
						res.body = JSON.parse(await res.response);
					}
					catch(e) {}
				}
			}
			finally {
				const uri = res.url?? (obj.path + obj.query);
				const headers = Object.fromEntries((res.headers?? new Headers()).entries());
				const body = JSON.stringify(res.body?? '{}', null, 2);

				$reqTime.innerText = 'Request executed in ' + (t1 - t0) + ' ms';

				$apiUri.href = uri;
				$apiUri.innerText = uri;

				$reqHdrs.innerText = JSON.stringify(headers, null, 2);
				$resBdy.innerText = body;

				if($responseWrapper.classList.contains('hidden')) {
					$responseWrapper.classList.remove('hidden');
				}
			}
		}

		$execBtn.removeAttribute('disabled');
	});
}, false);