<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');

?>

<p class="result-count">
	<span><?=$this->data['pagination']['resultCount']?></span>
	sur
	<span><?=$this->data['pagination']['maxResults']?></span>
	résultats
</p>
<div class="pages-actions">
	<a class="left-arrow<?=($this->data['pagination']['page']>1)?'':' disabled'?>"></a>
	<p>
		page
		<span><?=$this->data['pagination']['page']?></span>
		/
		<span><?=$this->data['pagination']['maxPage']?></span>
	</p>
	<a class="right-arrow<?=($this->data['pagination']['page']<$this->data['pagination']['maxPage'])?'':' disabled'?>"></a>
</div>