<?php
	$title = "noUiSlider - Handles and Slider Values";
	$description = "";
    $canonical = "nouislider/slider-values/"
?>

<h1>Slider values</h1>


<?php sect('handles'); ?>
<h2>Handles</h2>

<section>

	<div class="view">

		<p>The number of handles can be set using the <code>start</code> option. This option accepts an array of initial handle positions. A handle is created for every provided value.</p>

		<div class="example">
			<div id="slider-handles"></div>
			<?php run('handles'); ?>
		</div>
	</div>

	<div class="side">
		<?php code('handles'); ?>
	</div>
</section>

<?php sect('handles-multiple'); ?>
<h3>More than two handles</h3>

<section>

	<div class="view">

		<p>The number of handles in the <code>start</code> option is not limited to two. The <a href="/nouislider/slider-options/#section-connect">connect option</a> can be used between every handle.</p>

		<div class="example">
			<div id="slider-handles4"></div>
			<?php run('handles-multiple'); ?>
		</div>
	</div>

	<div class="side">
		<?php code('handles-multiple'); ?>
	</div>
</section>


<?php sect('range'); ?>
<h2>Range</h2>

<section>

	<div class="view">
		<p>All values on the slider are part of a range. The range has a minimum and maximum value. If the minimum value is equal to the maximum value, handles are evenly spread across the slider.</p>

		<div class="example">
			<div id="slider-range"></div>
			<span class="example-val" id="slider-range-value"></span>
			<?php run('range'); ?>
			<?php run('range-link'); ?>
		</div>
	</div>

	<div class="side">
		<?php code('range'); ?>

		<div class="viewer-header">Display the slider value</div>

		<div class="viewer-content">
			<?php code('range-link'); ?>
		</div>
	</div>
</section>


<?php sect('step'); ?>
<h3>Stepping and snapping to values</h3>

<section>

	<div class="view">
		<p>The amount the slider changes on movement can be set using the <code>step</code> option.</p>
		<div class="example">
			<div id="slider-step"></div>
			<span class="example-val" id="slider-step-value"></span>
			<?php run('step'); ?>
			<?php run('step-link'); ?>
		</div>
	</div>

	<div class="side">
		<?php code('step'); ?>

		<div class="viewer-header">Display the slider value</div>

		<div class="viewer-content">
			<?php code('step-link'); ?>
		</div>
	</div>
</section>


<?php sect('non-linear'); ?>
<h2>Non-linear sliders</h2>

<section>

	<div class="view">

		<p>noUiSlider offers some powerful mechanisms that allow a slider to behave in a non-linear fashion. Sliders can be created with ever-increasing increments by specifying the value for the slider at certain intervals. Note how the handle in the example below starts at 30% of the slider width, even though <code>4000</code> is not 30% of the span between <code>2000</code> and <code>10000</code>.</p>

		<div class="example">
			<div id="slider-non-linear"></div>
			<span class="example-val" id="slider-non-linear-value"></span>
			<?php run('non-linear'); ?>
			<?php run('non-linear-link'); ?>
		</div>
	</div>

	<div class="side">
		<?php code('non-linear'); ?>

		<div class="viewer-header">Display the slider value</div>

		<div class="viewer-content">
			<?php code('non-linear-link'); ?>
		</div>
	</div>
</section>


<?php sect('non-linear-step'); ?>
<h3>Stepping in non-linear sliders</h3>

<section>

	<div class="view">
		<p>For every range-segment in a non-linear slider, stepping can be set. Note how in the example below the slider doesn't step until it reaches <code>500</code>. From there on, it changes in increments of <code>500</code>, until it reaches <code>4000</code>, where increments now span <code>1000</code>.</p>

        <div class="notice">
            <p>Note that the <a href="/nouislider/slider-options/#section-step"><code>step</code> option</a>, when set, only applies to the first range-segment.</p>
        </div>

		<div class="example">
			<div id="slider-non-linear-step"></div>
			<span class="example-val" id="slider-non-linear-step-value"></span>
			<?php run('non-linear-step'); ?>
			<?php run('non-linear-step-link'); ?>
		</div>
	</div>

	<div class="side">
		<?php code('non-linear-step'); ?>

		<div class="viewer-header">Display the slider value</div>

		<div class="viewer-content">
			<?php code('non-linear-step-link'); ?>
		</div>
	</div>
</section>


<?php sect('snap'); ?>
<h3>Snapping between steps</h3>

<section>

	<div class="view">
		<p>When a non-linear slider has been configured, the <code>snap</code> option can be set to <code>true</code> to force the slider to jump between the specified values.</p>

		<div class="example">
			<div id="slider-snap"></div>
			<span class="example-val" id="slider-snap-value-lower"></span>
			<span class="example-val" id="slider-snap-value-upper"></span>
			<?php run('snap'); ?>
			<?php run('snap-link'); ?>
		</div>
	</div>

	<div class="side">
		<?php code('snap'); ?>
		<div class="viewer-header">Display the slider value</div>

		<div class="viewer-content">
			<?php code('snap-link'); ?>
		</div>
	</div>
</section>


<?php sect('steps'); ?>
<h3>Getting the next slider steps</h3>

<section>

	<div class="view">

		<p>Using the <code>steps</code> API, the sliders previous and next steps can be retrieved.</p>

		<p>The <a href="/nouislider/examples/#section-keypress">examples section</a> demonstrates how this API can be used.</p>
	</div>

	<div class="side">
		<?php code('steps'); ?>
	</div>
</section>
