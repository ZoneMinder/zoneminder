<?php sect('toggle'); ?>
<h1>Creating a toggle</h1>

<section>

	<div class="view">

		<p>Many application interfaces have options that can be turned on or off using switches. noUiSlider is well suited for this, especially because of the wide touch support.</p>

		<p>The <code>update</code> event can be used to keep track of changes to the handle. We'll set the range to <code>[0, 1]</code>, which leaves one step of <code>1</code>.</p>

		<div class="example vertical">
			<div id="slider-toggle"></div>
			<?php run('toggle'); ?>
		</div>
	</div>

	<div class="side">

		<div class="viewer-header">Toggle</div>

		<div class="viewer-content">
			<?php code('toggle'); ?>
		</div>

		<div class="viewer-header">CSS</div>

		<div class="viewer-content">
			<?php loadShowCSS('toggle'); ?>
		</div>

	</div>
</section>
