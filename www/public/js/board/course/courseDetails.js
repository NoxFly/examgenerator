/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

const $tabNav = document.getElementById('tab-nav');
const $tabContent = document.getElementById('tab-content');

$tabNav?.querySelectorAll('li').forEach($li => {
	$li.addEventListener('click', () => {
		if($li.classList.contains('selected')) {
			return;
		}

		$tabNav.querySelector('.selected')?.classList.remove('selected');
		$tabContent.querySelector('.selected')?.classList.remove('selected');

		$li.classList.add('selected');
		$tabContent.querySelector(`[data-i="${$li.dataset.i}"]`)?.classList.add('selected');
	});
});