<?php sect('colorpicker'); ?>
<h1>Colorpicker</h1>

<section>

	<div class="view">

		<p>We'll initialize all sliders with the same options, and use the <code>update</code> callback to keep to color in sync with the slider values.</p>

		<p>This callback fires any time a slider value updates.</p>

		<div id="colorpicker">
			<div class="sliders" id="red"></div>
			<div class="sliders" id="green"></div>
			<div class="sliders" id="blue"></div>

			<div id="result"></div>
			<?php run('colorpicker-slider'); ?>
		</div>
	</div>

	<div class="side">

		<div class="viewer-header">Colorpicker</div>

		<div class="viewer-content">
			<?php code('colorpicker-slider'); ?>
		</div>

		<div class="viewer-header">CSS</div>

		<div class="viewer-content">
			<?php loadShowCSS('colorpicker'); ?>
		</div>

	</div>
</section>
