<?php sect('skipping'); ?>
<h1>Skipping steps</h1>

<section>

	<div class="view">
		<p>When using a stepped slider, your configuration may require that certain steps aren't available. Using the <code>snap</code> feature, this effect can be created.</p>
		<p>Notice how <code>40</code> and <code>80</code> can't be selected on the slider.</p>

		<div class="example">
			<div id="skipstep"></div>
			<span class="example-val" id="skip-value-lower"></span>
			<span class="example-val" id="skip-value-upper"></span>
			<?php run('skipstep-slider'); ?>
			<?php run('skipstep-link'); ?>
		</div>
	</div>

	<div class="side">

		<div class="viewer-header">Initialize a snapping slider</div>

		<div class="viewer-content">
			<?php code('skipstep-slider'); ?>
		</div>

		<div class="viewer-header">Read the slider values</div>

		<div class="viewer-content">
			<?php code('skipstep-link'); ?>
		</div>
	</div>
</section>
