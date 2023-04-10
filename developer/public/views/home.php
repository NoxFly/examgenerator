<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


$tab = $this->req->params['api']?? NULL;
$class = '';

if(!$tab) {
	$class = 'empty';
}

$title = $tab?? ($this->getAppName() . '<br>Developer<br>API Portal');

?>


<nav>
	<h3><?=$this->getAppName()?> API</h3>
	<ul>
<?php foreach($this->data['tabs'] as $href) {

$tabClass = ($tab === $href)? 'active' : '';

?>
		<li class="<?=$tabClass?>"><a href="<?=$this->url("/$href")?>"><?=$href?></a></li>
<?php } ?>
	</ul>
</nav>

<div id="content-inner" class="<?=$class?>">
	<h1 class="<?=$class?>"><?=$title?></h1>

<?php if($tab) { ?>

	<article class="endpoints">
<?php
$j = 0;

foreach($this->data['endpoints'] as $edpt) {
	$j++;
?>
		<details class="endpoint">
			<summary>
				<span class="method" data-method-count="<?=count($edpt['methods'])?>">
<?php foreach($edpt['methods'] as $method) { ?>
					<span><?=$method?></span>
<?php } ?>
				</span>
				<span class="path"><?=$edpt['path']?></span>
			</summary>

			<section>
				<form>
					<div class="form-columns">
						<div class="left-column">
							<div class="param-row api-method">
								<label>
									<span class="param-name">Method</span>
								</label>
								<div class="select method-tabs">
<?php
foreach($edpt['methods'] as $i => $method) {
	if($i === 0) {
?>
									<opt data-value="<?=$method?>" data-selected data-idx="<?=$i?>"><?=$method?></opt>
<?php } else { ?>
									<opt data-value="<?=$method?>" data-idx="<?=$i?>"><?=$method?></opt>
<?php }
}
?>
								</div>
							</div>

<?php foreach($edpt['params'] as $param) { ?>
								<div class="param-row api-param">
									<label for="ipt-<?=$j?>-<?=$param[0]?>">
										<span class="param-name"><?=$param[0]?></span>
										<span class="label-required">required</span>
									</label>
									<input type="text" name="ipt-<?=$j?>-<?=$param[0]?>" id="ipt-<?=$j?>-<?=$param[0]?>" required/>
								</div>
<?php } ?>
							<div class="param-row api-token">
								<label for="ipt-<?=$j?>-token">
									<span class="param-name">token</span>
									<span class="label-optional">optional</span>
								</label>
								<input type="text" name="ipt-<?=$j?>-token" id="ipt-<?=$j?>-token"/>
							</div>

							<div class="token-location">
								<label for="token-loc-<?=$j?>-1">
									<input type="radio" name="token-location" id="token-loc-<?=$j?>-1" value="query" checked>
									Query Param
								</label>
								<label for="token-loc-<?=$j?>-2">
									<input type="radio" name="token-location" id="token-loc-<?=$j?>-2" value="header">
									Header Param
								</label>
							</div>
						</div>
						<div class="right-column">
							<div class="inner inner-query<?=($edpt['methods'][0]==='POST'||$edpt['methods'][0]==='PUT'||$edpt['methods'][0]==='PATCH')?' hidden':''?>">
								<h3>Query</h3>
								<div class="query-list">
									<div class="param-row api-query">
										<input type="text" class="query-key" placeholder="key">
										<input type="text" class="query-value" placeholder="value">
										<button class="delete-param" type="button" tabindex="-1"></button>
									</div>
								</div>
								<button class="add-param" type="button">+ Add parameter</button>
							</div>
							<div class="inner inner-body<?=($edpt['methods'][0]==='GET'||$edpt['methods'][0]==='DELETE')?' hidden':''?>">
								<h3>Body</h3>
								<div class="body-list">
									<div class="param-row api-body">
										<input type="text" class="body-key" placeholder="key">
										<input type="text" class="body-value" placeholder="value">
										<button class="delete-param" type="button" tabindex="-1"></button>
									</div>
								</div>
								<button class="add-param" type="button">+ Add parameter</button>
							</div>
						</div>
					</div>

					<span>
						<button class="exec-req primary" type="submit">Execute request</button>
						<span class="req-time"></span>
					</span>
				</form>

				<div class="response-wrapper hidden">
					<div class="api-uri">
						<h5>Requested URI</h5>
						<div class="inner">
							<a target="_blank" href=""></a>
						</div>
					</div>

					<div class="request-headers">
						<h5>Request Headers</h5>
						<div class="inner">
							<pre></pre>
						</div>
					</div>
					<div class="response-body">
						<h5>Response Body</h5>
						<div class="inner">
							<pre></pre>
						</div>
					</div>
				</div>
			</section>
		</details>
<?php } ?>
	</article>

<?php } ?>
</div>