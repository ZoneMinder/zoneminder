<?php sect('steps-api'); ?>
<h1>Using the steps API</h1>

<section>

	<div class="view">

		<p>noUiSlider provides a <code>steps</code> API to determine the previous and next steps for a handle. In this example, pressing the keyboard arrow keys will increase/decrease the slider by one step.</p>

		<p>Use of this API is not necessary for linear sliders, as the step is constant in that case.</p>

		<p>A <code>keydown</code> listener is added to the input element, passing the event to a function to read the <code>code</code> that identifies the key.</p>

		<div class="example">
			<div id="steps-slider"></div>
			<input type="text" id="input-with-keypress-1">
			<input type="text" id="input-with-keypress-0">
			<?php run('steps-slider'); ?>
			<?php run('steps-event'); ?>
		</div>
	</div>

	<div class="side">

		<div class="viewer-header">Initializing the slider and linking the input</div>

		<div class="viewer-content">
			<?php code('steps-slider'); ?>
		</div>

		<div class="viewer-header">Listen to <code>keypress</code> on the input</div>

		<div class="viewer-content">
			<?php code('steps-event'); ?>
		</div>

	</div>
</section>
