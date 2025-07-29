<?php sect('html5'); ?>
<h1>Using HTML5 input elements</h1>

<section>

	<div class="view">
		<p>noUiSlider's <code>'update'</code> method is useful for synchronizing with other elements, such as <code>&lt;input&gt;</code> (<code>type="number"</code>) and <code>&lt;select&gt;</code>.</p>

		<div class="example">
			<div id="html5"></div>
			<select id="input-select"></select>
			<input type="number" min="-20" max="40" step="1" id="input-number">
			<?php run('html5-append'); ?>
			<?php run('html5-slider'); ?>
			<?php run('html5-link'); ?>
		</div>
	</div>

	<div class="side">

		<div class="viewer-header">Appending <code>&lt;option&gt;</code> elements</div>

		<div class="viewer-content">
			<?php code('html5-append', true); ?>
		</div>

		<div class="viewer-header">Initializing the slider</div>

		<div class="viewer-content">
			<?php code('html5-slider'); ?>
		</div>

		<div class="viewer-header">Updating the <code>&lt;select&gt;</code> and <code>&lt;input&gt;</code></div>

		<div class="viewer-content">
			<?php code('html5-link'); ?>
		</div>

		<div class="viewer-header">Example CSS</div>

		<div class="viewer-content">
			<?php loadShowCSS('html5'); ?>
		</div>
	</div>
</section>
