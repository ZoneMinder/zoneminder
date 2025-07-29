<?php sect('non-linear'); ?>
<h1>Non linear slider</h1>

<section>

	<div class="view">

		<p>One of noUiSlider's core features is the ability to divide the range in a non-linear fashion. Stepping can be applied. This example shows where the handles are on the slider range in values and percentages.</p>

		<div class="example">
			<div id="nonlinear"></div>
			<span class="example-val" id="lower-value"></span>
			<span class="example-val" id="upper-value"></span>
			<?php run('nonlinear-slider'); ?>
			<?php run('nonlinear-link'); ?>
		</div>
	</div>

	<div class="side">

		<div class="viewer-header">Setting up the slider</div>

		<div class="viewer-content">
			<?php code('nonlinear-slider'); ?>
		</div>

		<div class="viewer-header">Read the slider value and the left offset</div>

		<div class="viewer-content">
			<?php code('nonlinear-link'); ?>
		</div>
	</div>
</section>
