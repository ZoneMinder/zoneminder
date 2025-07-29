<?php sect('colored-connect'); ?>
<h1>Colored Connect Elements</h1>

<section>

	<div class="view">

		<p>noUiSlider's connect elements can be individually colored or otherwise styled.</p>

		<div class="example">
			<div class="slider" id="slider-color"></div>
			<?php run('colored-setup'); ?>
			<?php run('colored'); ?>
		</div>
	</div>

	<div class="side">

		<div class="viewer-header">Slider setup</div>

		<div class="viewer-content">
			<?php code('colored-setup'); ?>
		</div>

		<?php code('colored'); ?>
		<?php loadShowCSS('colored'); ?>
	</div>
</section>
