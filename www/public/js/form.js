/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

const valueChangedEvent = new Event('valueChanged');
const changedEvent = new Event('changed');

document.body.querySelectorAll('.select').forEach(prepareSelect);
document.body.querySelectorAll('.search-bar').forEach(prepareSearchBar);
document.body.querySelectorAll('.switch-1').forEach(prepareSwitch);
document.body.querySelectorAll('.input-middle-range input[type="range"]').forEach(prepareMiddleRange);


/**
 * If the value of a label in options is `null`, the value will be the label.
 * @param {string} defaultLabel 
 * @param {{label: string, value: string, selected?: boolean}[]} options 
 * @param {boolean} multiple
 * @return {HTMLDivElement}
 */
export function createSelect(defaultLabel, options={}, multiple=false) {
	const $slct = document.createElement('div');
	$slct.className = 'select' + (multiple? ' multiple' : '');

	for(const option of options) {
		const label = ((option.label?? '') + '').trim();
		const value = option.value;
		const selected = option.selected || false;

		const $opt = document.createElement('opt');
		$opt.innerText = label;

		if(value !== null) {
			$opt.dataset.value = value;
		}

		if(selected) {
			$opt.setAttribute('data-selected', '');
		}

		$slct.appendChild($opt);
	}

	$slct.insertAdjacentText('afterbegin', defaultLabel);

	prepareSelect($slct);

	return $slct;
}

/**
 * 
 * @param {HTMLElement} $select 
 */
export function prepareSelect($select) {
	$select.dataset.value = '';
	let oldValue = '';

	const isMultiple = $select.classList.contains('multiple');

	const text = $select.childNodes[0]?.textContent.trim() || '';

	const defaultLabel = text;
	const $oldOptions = $select.querySelectorAll('opt');

	$select.innerHTML = '';

	const $defaultLabel = document.createElement('span');
	$defaultLabel.innerText = defaultLabel;


	const $options = document.createElement('div');

	let $selected;

	for(const $opt of $oldOptions) {
		$opt.innerText = $opt.innerText.trim();
		$opt.setAttribute('title', $opt.innerText);
		$options.append($opt);

		if($opt.hasAttribute('data-selected')) {
			$selected = $opt;
		}

		if(!$opt.hasAttribute('data-value')) {
			$opt.dataset.value = $opt.innerText;
		}
	}

	$select.append($defaultLabel, $options);


	$defaultLabel.addEventListener('click', () => {
		if($select.classList.contains('disabled')) {
			return;
		}

		$select.classList.toggle('focus');

		if(!$select.classList.contains('focus')) {
			if(isMultiple && $select.dataset.value !== oldValue) {
				oldValue = $select.dataset.value;
				$select.dispatchEvent(changedEvent);
			}
		}
	});

	$options.addEventListener('click', e => {
		/** @type {HTMLDivElement} */
		const $option = e.target;

		if($select.classList.contains('disabled') || $option.classList.contains('disabled')) {
			return;
		}

		const label = $option.innerText;
		const value = $option.dataset.value?? label;

		if(isMultiple) {
			if(value === '*') {
				$select.dataset.value = value;
				$defaultLabel.innerText = defaultLabel;
	
				$options.querySelectorAll('opt.selected').forEach($opt => $opt.classList.remove('selected'));
				$option.classList.add('selected');
			}
			else {
				if($select.dataset.value === '*') {
					$options.querySelector('opt.selected')?.classList.remove('selected');
				}

				$option.classList.toggle('selected');
				
				const values = [...$options.querySelectorAll('opt.selected')]
					.map($el => $el.dataset.value?? $el.innerText)
					.join(';');

				$select.dataset.value = values;
			}

			$select.dispatchEvent(valueChangedEvent);
		}
		else {
			if(value !== $select.dataset.value) {
				$defaultLabel.innerText = label;
				$select.dataset.value = value;

				$select.dispatchEvent(valueChangedEvent);
				$select.dispatchEvent(changedEvent);
			}

			$select.classList.remove('focus');
		}
	}, false);


	$select.reset = () => {
		$defaultLabel.innerText = defaultLabel;
		$select.dataset.value = '';

		if(isMultiple) {
			$options.querySelectorAll('opt.selected').forEach($opt => $opt.classList.remove('selected'));
		}

		$selected?.click();
	};

	$select.select = value => {
		$options.querySelector(`[data-value="${value}"]`)?.click();
	}

	$select.removeOption = value => {
		$options.querySelector(`[data-value="${value}"]`)?.remove();
	}

	$select.disable = (option=null) => {
		if(option === null) {
			$select.classList.add('disabled');
		}
		else {
			$options.querySelector(`[data-value="${value}"]`)?.classList.add('disabled');
		}
	};

	$select.enable = (option=null) => {
		if(option === null) {
			$select.classList.remove('disabled');
		}
		else {
			$options.querySelector(`[data-value="${value}"]`)?.classList.remove('disabled');
		}
	};

	$selected?.click();
}

export function prepareSearchBar($searchbar) {
	const $input = $searchbar.querySelector('input');

	if($input) {
		$input.addEventListener('focus', () => {
			$searchbar.classList.add('focus');
		});

		$input.addEventListener('blur', () => {
			$searchbar.classList.remove('focus');
		});
	}
}

export function prepareSwitch($switch) {
	$switch.dataset.value = '';

	$switch.addEventListener('click', e => {
		/** @type {HTMLInputElement} */
		const $input = e.target;

		const $tab = $input.parentNode;

		if($tab.classList.contains('active')) {
			return;
		}

		$switch.querySelector('.active')?.classList.remove('active');
		$tab.classList.add('active');

		$switch.dataset.value = $input.value;

		$switch.dispatchEvent(valueChangedEvent);
	}, false);

	const $active = $switch.querySelector('.active input');

	if($active) {
		$switch.dataset.value = $active.value;
	}
}

export function prepareMiddleRange($ipt) {
	$ipt.addEventListener('input', () => updateRange($ipt));
}

export function updateRange($ipt) {
	const max = +$ipt.getAttribute('max');
	const min = +$ipt.getAttribute('min');

	const style = window.getComputedStyle($ipt);
	
	const middleValue = (max - min) / 2;
	const width = Math.round(+style.width.replace('px', ''));
	const height = Math.round(+style.height.replace('px', ''));

	const stepsCount = (min < max)? Math.abs(min) + Math.abs(max) : Math.abs(max) - Math.abs(min);
	const widthPerStep = width / stepsCount;

	const value = $ipt.value;

	const trackWidth = Math.round(Math.abs(middleValue - value) * widthPerStep * 100) / 100;

	const trackLeft = (middleValue >= $ipt.value)
		? width / 2 + height / 2 - trackWidth
		: width / 2;

	const trckWdthPerc = trackWidth * 100 / width;
	const trckLftPerc = trackLeft * 100 / width;

	const boundMin = +style.getPropertyValue('--min').replace('%', '');
	const boundMax = +style.getPropertyValue('--max').replace('%', '');

	let finalLeft = trckLftPerc;
	let finalWidth = trckWdthPerc;

	if(finalLeft <= boundMin) {
		finalWidth -= boundMin - finalLeft;
		finalLeft = boundMin;
	}

	else if(finalLeft + finalWidth >= boundMax) {
		finalWidth = boundMax - finalLeft;
	}

	$ipt.parentElement.style.setProperty('--track-width', finalWidth + '%');
	$ipt.parentElement.style.setProperty('--track-left', finalLeft + '%');
}