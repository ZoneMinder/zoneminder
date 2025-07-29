<?php
	$title = "noUiSlider - Getting and setting slider values";
	$description = "Reading and setting values from a slider is as easy as reading from any other form element.";
    $canonical = "nouislider/slider-read-write/"
?>

<h1>Getting and setting slider values</h1>


<?php sect('reading'); ?>
<h2>Reading slider values</h2>

<section>

	<div class="view">

		<p>noUiSlider has an API with two simple methods: <code>.get()</code> and <code>.set()</code>. To get the current slider value:</p>

		<?php code('read'); ?>

		<p>For one-handle sliders, calling <code>.get()</code> will return the value as a <code>'string'</code>. For multi-handle sliders, an <code>array['string', 'string', ...]</code> will be returned. Use <code>.get(true)</code> to get the slider values without formatting applied (as a <code>number</code> or <code>array[number, number, ...]</code>).</p>
	</div>
</section>


<?php sect('setting'); ?>
<h2>Setting slider values</h2>

<section>

	<div class="view">

        <p>If a slider is configured to use one handle, its current value can be changed using the <code>.set()</code> method.</p>

        <p>For sliders with multiple handles, pass an array. One-handled sliders will also accept arrays.</p>

		<p>Within an array, any position can be set to <code>null</code> to leave a handle unchanged.</p>

        <p>noUiSlider will always limit values to the slider range.</p>

        <?php sect('set-handle'); ?>
        <h3>setHandle</h3>

        <p>To set a single slider handle, the <code>setHandle</code> method can be used. This method accepts a zero-indexed handle number, a value and optionally a 'fire set event' boolean.</p>

        <p>Passing <code>null</code> as the value to <code>setHandle</code> will leave the handle unchanged.</p>

        <?php sect('exact-input'); ?>
        <h3>exactInput</h3>

        <p>Both the <code>set</code> and <code>setHandle</code> methods have an <code>exactInput</code> argument, which can be used to ignore the stepping configured for the slider.</p>

        <?php sect('reset'); ?>
        <h3>reset</h3>

		<p>To return to the initial slider values, the <code>.reset()</code> method can be used. This will <strong>only</strong> reset the slider <i>values</i>.</p>
	</div>

	<div class="side">
		<?php code('write'); ?>
	</div>
</section>


<?php sect('example'); ?>
<h2>Example</h2>

<section>

	<div class="view">

		<p>Clicking the button below sets the slider, which uses a <code>range</code> from <code>0</code> to <code>100</code>, to <code>20</code>.</p>

		<div class="example">
			<div id="slider"></div>
			<div class="logger">
				<button id="write-button">Set to 20</button>
				<button id="read-button">Read slider value</button>
			</div>
			<?php run('set', false); ?>
			<?php run('buttons', false); ?>
		</div>
	</div>

	<div class="side">
		<?php code('set'); ?>

		<div class="viewer-header">Handling button presses</div>

		<div class="viewer-content">
			<?php code('buttons'); ?>
		</div>
	</div>

</section>


<?php sect('positions'); ?>
<h2>Positions</h2>

<section>

	<div class="view">
        <p>To get the current positions for the slider handles (in percentages from the left of the slider), the <code>getPositions</code> method can be used. This matches the <a href="/nouislider/events-callbacks/#section-binding">positions parameter</a> in the slider's events.</p>
	</div>

    <div class="side">
        <?php code('positions'); ?>
    </div>

</section>


<?php sect('formatting'); ?>
<h2>Number formatting</h2>

<section>

	<div class="view">
        <p>See <a href="/nouislider/number-formatting/">the documentation on number formatting</a>.</p>
	</div>

</section>
