<?php
	$title = "noUiSlider - Disabling, Updating and Styling";
	$description = "In-dept details on disabling slider handles, updating options and styling noUiSlider.";
    $canonical = "nouislider/more/"
?>

<section>
	<ul>
		<li><a href="#section-disable">Disabling a slider or handles</a></li>
		<li><a href="#section-update">Updating slider options</a></li>
		<li><a href="#section-styling">Styling noUiSlider</a></li>
	</ul>
</section>


<?php sect('disable'); ?>
<h2>Disabling a slider</h2>

<section>

	<div class="view">

        <p>A slider can be disabled by calling the <code>disable</code> method in the API.</p>

		<p>A disabled slider can't be changed by user interaction (sliding, clicking or touching), but its value can still be changed using the <code>.set()</code> method.</p>

		<p>CSS can be used to show the disabled state. The default stylesheet also sets a <code>not-allowed</code> cursor.</p>

		<p>The slider below is disabled when the checkbox is checked, and re-enabled when it is unchecked.</p>

		<p>Individual handles can be disabled by calling the <code>disable</code> with a zero-index handle number. Disabling an individual handle will also prevent it from having <code>focus</code>.</p>

        <p>The slider or handles can re re-enabled using the <code>enable</code> method.</p>

		<div class="example">
			<div id="disable1"></div>
			<label>
				<input type="checkbox" id="checkbox1">
				Disable slider
			</label>
		</div>

		<div class="example">
			<div id="disable2"></div>
			<label>
				<input type="checkbox" id="checkbox2">
				Disable handle 1
			</label>
			<br>
			<label>
				<input type="checkbox" id="checkbox3">
				Disable handle 2
			</label>
			<?php run('disable', false); ?>
		</div>
	</div>

	<div class="side">

		<?php code('disable-all'); ?>

		<div class="viewer-header">Toggling the <code>disabled</code> attribute</div>

		<div class="viewer-content">
			<?php code('disable'); ?>
		</div>
	</div>

</section>

<?php sect('update'); ?>
<h2>Updating and reading slider options</h2>

<section>

	<div class="view">

        <p>noUiSlider has an update method that can change the <code>'margin'</code>, <code>'padding'</code>, <code>'limit'</code>, <code>'step'</code>, <code>'range'</code>, <code>'pips'</code>, <code>'tooltips'</code>, <code>'connect'</code>, <code>'animate'</code> and <code>'snap'</code> options.</p>

		<p>All other options require changes to the slider's HTML or event bindings.</p>

		<p>To update any other option, destroy the slider using <code>slider.noUiSlider.destroy()</code> and create a new one. Events are unbound when destroying a slider.</p>

		<p>The update method can be called as:</p>

<pre><code>slider.noUiSlider.updateOptions(
	newOptions, // Object
	true // Boolean 'fireSetEvent'
);</code></pre>

		<p>Options that can not be updated will be ignored without errors.</p>

        <p>The value <code>null</code> can be used to unset a previously set value.</p>

		<p>The <code>'update'</code> event fires after updating the slider.</p>

		<p>By default, the sliders <strong>values remain unchanged</strong>. To update the slider values, <code>newOptions</code> may also contain a <code>start</code> property that matches the signature of the <a href="/nouislider/slider-read-write/#section-setting"><code>.set()</code></a> method.</p>

		<p>The <code>'set'</code> event fires when the slider values are restored. If this is unwanted, <code>false</code> can be passed as the second parameter, <code>fireSetEvent</code>.</p>

		<p>Options can be read from the slider using the <code>slider.noUiSlider.options</code> property. This property contains a <strong>reference</strong> to the options object passed when creating the slider. This object is <strong>modified</strong> when calling <code>updateOptions</code>.</p>

		<p>Note that if multiple sliders are initiated using the same options object and a subset of them is updated later, this <em>will</em> move the <code>options</code> property out of sync with the actual slider options.</p>

		<div class="example">
			<div id="slider-update"></div>
			<span class="example-val" style="margin-top: 70px;" id="slider-update-value"></span>
            <button class="update-button" id="update-1">Set range [20, 50]</button>
            <button class="update-button" id="update-2">Set range [10, 40]</button>
            <button class="update-button" id="update-3">Tooltips, no Pips</button>
            <button class="update-button" id="update-4">Pips, no Tooltips</button>
            <button class="update-button" id="update-5">Add padding</button>
            <button class="update-button" id="update-6">Remove padding</button>
            <?php run('update-setup'); ?>
            <?php run('update'); ?>
		</div>
    </div>

	<div class="side">

		<div class="viewer-header">HTML for this example</div>

		<div class="viewer-content">

<pre class="language-markup"><code>&lt;div id="update"&gt;&lt;/div&gt;
&lt;span id="value"&gt;&lt;/span&gt;

&lt;button class="update-button" id="update-1"&gt;
	Set range [20, 50]
&lt;/button&gt;

&lt;button class="update-button" id="update-2"&gt;
	Set range [10, 40]
&lt;/button&gt;</code></pre>

		</div>

		<div class="viewer-header">Setting up the slider</div>

		<div class="viewer-content">
			<?php code('update-setup'); ?>
		</div>

		<div class="viewer-header">Updating the slider with new options on <code>button</code> click</div>

		<div class="viewer-content">
			<?php code('update'); ?>
		</div>
	</div>

</section>


<?php sect('styling'); ?>
<h2>Styling</h2>

<section>

	<div class="view">
		<p>To style noUiSlider the default stylesheet contains helpful comments to get a head start.</p>

		<p>It is recommended to use the default stylesheet, overriding where necessary, as a starting point when re-styling noUiSlider.</p>

		<p>If your styling system doesn't match the convention in noUiSlider, the <code>cssPrefix</code> and <code>cssClasses</code> options can be used to reconfigure the markup.</p>

        <p>Alternatively, to modify the default classes for all sliders, a modifiable reference to the default class list is available as <code>noUiSlider.cssClasses</code>.</p>
	</div>

	<div class="side">

		<div class="viewer-header">CSS classes overview</div>

		<div class="viewer-content">

			<table class="language-css data-table styling-table">
				<thead>
					<tr>
						<th width="250">Selector</th>
						<th>Effect</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><code>.noUi-target</code></td>
						<td>This class is added to the element <code>noUiSlider.create()</code> is called on. Has <code>border</code>, <code>background</code> and other styling properties to establish the slider design and background.</td>
					</tr>
					<tr>
						<td><code>.noUi-base</code></td>
						<td>The slider bar. Serves as the calculating point for the slider handles, and has no visible styling.</td>
					</tr>
					<tr>
						<td><code>.noUi-origin</code></td>
						<td>The elements connected to the base, defining the handle locations.</td>
					</tr>
					<tr>
						<td><code>.noUi-handle</code></td>
						<td>The actual, visible handles.</td>
					</tr>
					<tr>
						<td><code>.noUi-touch-area</code></td>
						<td>An empty div within <code>.noUi-handle</code>. Can be styled larger if desired.</td>
					</tr>
					<tr>
						<td><code>.noUi-connect</code></td>
						<td>Styling class for setting properties related to the slider <code>connect</code> segment.</td>
					</tr>
					<tr>
						<td><code>.noUi-state-tap</code></td>
						<td>This class is added when the slider bar is tapped or clicked. A slider with this call will reject any user input. noUiSlider will remove this class after <code>300ms</code>, leaving that timespan to perform visual animations.</td>
					</tr>
					<tr>
						<td><code>[disabled]</code></td>
						<td>Apply this to your slider to disable it, and make sure the slider visuals reflect the disabled state.</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="viewer-header">Overriding classes</div>

		<div class="viewer-content">
			<?php code('classes'); ?>
		</div>

	</div>

</section>
