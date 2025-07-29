<?php sect('soft-limits'); ?>
<h1>Soft limits</h1>

<section>

	<div class="view">

		<p>To disable the edges of a slider, the set event can be used to reset the value if a limit is passed. Note how the handle 'bounces back' when it is released below <code>20</code> or above <code>80</code>. noUiSlider also supports disabling edges altogether, which can be done using the <a href="/nouislider/slider-options/#section-padding">padding option</a>.</p>

		<div class="example">
			<div id="soft"></div>
			<?php run('slider-soft-setup'); ?>
			<?php run('slider-soft'); ?>
		</div>
	</div>

	<div class="side">

		<div class="viewer-header">Setting up the slider</div>

		<div class="viewer-content">
			<?php code('slider-soft-setup'); ?>
		</div>

		<div class="viewer-header">Resetting using the <code>change</code> event</div>

		<div class="viewer-content">
			<?php code('slider-soft'); ?>
		</div>
	</div>
</section>
