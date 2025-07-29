<?php
	$title = "noUiSlider - Options and settings";
	$description = "There are many options to make noUiSlider do exactly what you need. Read all about these options and find the one you need.";
    $canonical = "nouislider/slider-options/"
?>

<h1>Options</h1>

<section>

	<div class="double">

		<p>noUiSlider can be configured with a wide variety of options, which can be used to customize slider functionality.</p>

		<p>For options regarding the slider range, see <a href="/nouislider/slider-values/"> slider values</a>.</p>

		<p>For options regarding the sliders behaviour, see <a href="/nouislider/behaviour-option/"> slider behaviour</a>.</p>
	</div>
</section>

<section>
    <div class="notice">For a complete overview of all slider options, method and properties, see <a href="/nouislider/reference/"> reference</a>.</div>
</section>

<section>
	<ul>
		<li><a href="#section-start">Start</a></li>
		<li><a href="#section-connect">Connect</a></li>
		<li><a href="#section-margin">Margin</a></li>
		<li><a href="#section-limit">Limit</a></li>
		<li><a href="#section-padding">Padding</a></li>
		<li><a href="#section-step">Step</a></li>
		<li><a href="#section-orientation">Orientation</a></li>
		<li><a href="#section-direction">Direction</a></li>
		<li><a href="#section-tooltips">Tooltips</a></li>
		<li><a href="#section-animate">Animate</a></li>
		<li><a href="#section-handle-attributes">Handle Attributes</a></li>
		<li><a href="#section-keyboard-support">Keyboard Support</a></li>
		<li><a href="#section-document-element">Document Element <small>(advanced)</small></a></li>
	</ul>
</section>


<?php sect('start'); ?>
<h2>Start</h2>

<section>

	<div class="view">

		<p>The start option sets the number of handles and corresponding start positions.</p>

		<p>The start option uses the slider's <code>'format'</code> option to decode the input. Number input will be cast to string and decoded.</p>

		<div class="example">
			<div id="slider-start"></div>
			<?php run('start', false); ?>
		</div>

		<div class="options">
			<strong>Default</strong>
			<div><em>none</em></div>

			<strong>Accepted values</strong>
			<div><code>'string'</code>,<br>
				<code>array['string']</code>,<br>
				<code>array['string', 'string', ...]</code>
			</div>
		</div>
	</div>

	<div class="side">
		<?php code('start'); ?>
	</div>

</section>


<?php sect('connect'); ?>
<h2>Connect</h2>

<section>

	<div class="view">

		<p>The connect option can be used to control the bar between the handles or the edges of the slider.</p>

        <p>When using one handle, set the value to either <code>'upper'</code> or <code>'lower'</code>.</p>

		<div class="example">
			<div id="slider-connect"></div>
			<?php run('connect'); ?>
		</div>

		<p>For sliders with 2 or more handles, pass an <code>array</code> with a <code>boolean</code> for every connecting element, including the edges of the slider. The length of this array must match the handle count + 1.</p>

		<p>Setting <code>true</code> sets the bars between the handles, but not between the handles and the sliders edges.</p>

		<div class="example">
			<div id="slider-connect2"></div>
			<?php run('connect-more'); ?>
		</div>

		<div class="options">
			<strong>Default</strong>
			<div><code>false</code></div>

			<strong>Accepted values</strong>
			<div>
				<code>'lower'</code>,<br>
				<code>'upper'</code>,<br>
				<code>true</code>,<br>
				<code>false</code>,<br>
				<code>array[...]</code>
			</div>
		</div>

	</div>

	<div class="side">
		<?php code('connect'); ?>
		<?php code('connect-more'); ?>
	</div>

</section>


<?php sect('margin'); ?>
<h2>Margin</h2>

<section>

	<div class="view">

		<p>When using two handles, the minimum distance between the handles can be set using the margin option. The margin value is relative to the value set in 'range'.</p>

		<div class="example">
			<div id="slider-margin"></div>
			<span class="example-val" id="slider-margin-value-min"></span>
			<span class="example-val" id="slider-margin-value-max"></span>
			<?php run('margin'); ?>
			<?php run('margin-link'); ?>
		</div>

		<div class="options">
			<strong>Default</strong>
			<div><em>none</em></div>

			<strong>Accepted values</strong>
			<div><code>number</code></div>
		</div>
	</div>

	<div class="side">
		<?php code('margin'); ?>

		<div class="viewer-header">Show the slider value</div>

		<div class="viewer-content">
			<?php code('margin-link'); ?>
		</div>
	</div>

</section>


<?php sect('limit'); ?>
<h2>Limit</h2>

<section>

	<div class="view">

		<p>The limit option is the opposite of the margin option, limiting the maximum distance between two handles.</p>

		<div class="example">
			<div id="slider-limit"></div>
			<span class="example-val" id="slider-limit-value-min"></span>
			<span class="example-val" id="slider-limit-value-max"></span>
			<?php run('limit'); ?>
			<?php run('limit-link'); ?>
		</div>

		<div class="options">
			<strong>Default</strong>
			<div><em>none</em></div>

			<strong>Accepted values</strong>
			<div><code>number</code>
			</div>
		</div>
	</div>

	<div class="side">
		<?php code('limit'); ?>

		<div class="viewer-header">Show the slider value</div>

		<div class="viewer-content">
			<?php code('limit-link'); ?>
		</div>
	</div>

</section>

<?php sect('padding'); ?>
<h2>Padding</h2>

<section>

	<div class="view">

		<p>Padding limits how close to the slider edges handles can be.</p>

		<div class="example">
			<div id="slider-padding"></div>
			<span class="example-val" id="slider-padding-value-min"></span>
			<span class="example-val" id="slider-padding-value-max"></span>
			<?php run('padding'); ?>
			<?php run('padding-link'); ?>
		</div>

		<div class="options">
			<strong>Default</strong>
			<div><em>0</em></div>

			<strong>Accepted values</strong>
			<div><code>number</code>,<br>
				<code>array[number]</code>,<br>
				<code>array[number, number]</code>
			</div>
		</div>
	</div>

	<div class="side">
		<?php code('padding'); ?>

		<div class="viewer-header">Show the slider value</div>

		<div class="viewer-content">
			<?php code('padding-link'); ?>
		</div>
	</div>

</section>


<?php sect('step'); ?>
<h2>Step</h2>

<section>

	<div class="view">
		<p>By default, the slider slides fluently. In order to make the handles jump between intervals, the step option can be used.</p>

        <div class="notice">
            <p>Note that for non-linear sliders, <code>step</code> values are set as part of the <a href="/nouislider/slider-values/#section-range"><code>range</code> option</a>.</p>
        </div>

		<div class="example">
			<div id="slider-step"></div>
			<?php run('step'); ?>
		</div>

		<div class="options">
			<strong>Default</strong>
			<div><em>none</em></div>

			<strong>Accepted values</strong>
			<div><code>number</code>
			</div>
		</div>
	</div>

	<div class="side">
		<?php code('step'); ?>
	</div>

</section>


<?php sect('orientation'); ?>
<h2>Orientation</h2>

<section>

	<div class="view">

		<p>The orientation setting can be used to set the slider to <code>"vertical"</code> or <code>"horizontal"</code>.</p>

		<div class="notice"><strong>Set dimensions!</strong> Vertical sliders don't assume a default <code>height</code>, so a height needs to be set. Any unit can be used, including <code>px</code>, <code>em</code> or <code>rem</code>.</div>

		<div class="example vertical">
			<div id="slider-vertical"></div>
			<?php run('orientation'); ?>
		</div>

		<div class="options">
			<strong>Default</strong>
			<div><code>"horizontal"</code></div>

			<strong>Accepted values</strong>
			<div><code>"vertical"</code>, <code>"horizontal"</code></div>
		</div>
	</div>

	<div class="side">
		<?php code('orientation'); ?>
	</div>

</section>


<?php sect('direction'); ?>
<h2>Direction</h2>

<section>

	<div class="view">

		<p>By default the sliders are <em>top-to-bottom</em> and <em>left-to-right</em>, but this can be changed using the direction option, which decides where the upper side of the slider is.</p>

		<div class="example">
			<div id="slider-direction"></div>
			<div class="example-val" id="field"></div>
			<?php run('direction'); ?>
			<?php run('direction-link'); ?>
		</div>

		<div class="options">
			<strong>Default</strong>
			<div><code>"ltr"</code></div>

			<strong>Accepted values</strong>
			<div><code>"ltr"</code>, <code>"rtl"</code></div>
		</div>
	</div>

	<div class="side">

		<?php code('direction'); ?>

		<div class="viewer-header">Show the slider value</div>

		<div class="viewer-content">
			<?php code('direction-link'); ?>
		</div>
	</div>

</section>


<?php sect('tooltips'); ?>
<h2>Tooltips</h2>

<section>

	<div class="view">

		<p>noUiSlider can provide a basic tooltip using the <code>tooltips</code> option. This option can also accept <a href="/nouislider/number-formatting/#section-tooltips">formatting options</a> to format the tooltips content. In that case, pass an <code>array</code> with a formatter for each handle, <code>true</code> to use the default or <code>false</code> to display no tooltip.</p>

        <p>To merge overlapping tooltips, refer to <a href="/nouislider/examples/#section-merging-tooltips">this example</a>.</p>

        <?php sect('remove-tooltips'); ?>
        <h3>removeTooltips</h3>

        <p>Tooltips can be removed from a slider using the <code>removeTooltips()</code> method.</p>

		<div class="example overflow">
			<div id="slider-tooltips"></div>
			<?php run('tooltips'); ?>
		</div>

		<div class="options">
			<strong>Default</strong>
			<div><code>false</code></div>

			<strong>Accepted values</strong>
			<div><code>false</code>, <code>true</code>, <code>formatter</code>, <code>array[formatter or true or false, ...]</code></div>
		</div>
	</div>

	<div class="side">

		<?php code('tooltips'); ?>
	</div>

</section>


<?php sect('animate'); ?>
<h2>Animate</h2>

<section>

	<div class="view">

		<p>Set the animate option to <code>false</code> to prevent the slider from animating to a new value with when calling <code>.set()</code>.</p>

		<div class="example" style="margin: 0; padding-bottom: 20px">
			<div class="sliders" id="slider-slow"></div>
            <?php run('animate-duration'); ?>
			<button id="set-sliders">Set sliders</button>
		</div>

		<div class="example" style="margin: 0;">
			<div class="sliders" id="slider-animate-false"></div>
			<?php run('animate'); ?>
		</div>

		<div class="options">
			<strong>Default</strong>
			<div><code>true</code></div>

			<strong>Accepted values</strong>
			<div><code>true</code>, <code>false</code></div>
		</div>

		<br>

        <h3>Animation Duration</h3>

		<p>The <code>animationDuration</code> option can be used to set the animation speed assumed by the slider library.</p>

        <p>In addition to this, the CSS <code>(-webkit-)transition</code> property for the <code>.noUi-state-tap .noUi-origin</code> and <code>.noUi-state-tap .noUi-connect</code> selectors must be set.</p>

		<div class="options">
			<strong>Default</strong>
			<div><code>300</code></div>

			<strong>Accepted values</strong>
			<div><code>number</code></div>
		</div>
	</div>

	<div class="side">
		<?php code('animate'); ?>

        <div class="viewer-header">Changing animation duration</div>

        <div class="viewer-content">
            <?php code('animate-duration'); ?>
            <?php loadShowCSS('animate-duration'); ?>
        </div>
	</div>

</section>


<?php sect('handle-attributes'); ?>
<h2>Handle Attributes</h2>

<section>

    <div class="view">

        <p>Additional attributes can be added to the slider handles using the <code>handleAttributes</code> option.</p>

        <div class="options">
            <strong>Default</strong>
            <div><em>[none]</em></div>

            <strong>Accepted values</strong>
            <div>array of <code>{ key: value }</code> for each handle</div>
        </div>
    </div>

    <div class="side">

        <?php code('handle-attributes'); ?>
    </div>

</section>


<?php sect('keyboard-support'); ?>
<h2>Keyboard Support</h2>

<section>

    <div class="view">

        <p>Handles in the slider can receive keyboard focus and be moved by arrow keys.</p>

		<p>Keyboard support can be disabled by setting the <code>keyboardSupport</code> option to <code>false</code>.</p>

        <p>When moved by the arrow keys on a keyboard, handles obey the <code>step</code> value for the range they are in.
            When moving in a range that has no <code>step</code> value set, handles move by 10% <i>of the range they are in</i>.
            This default can be changed using the <code>keyboardDefaultStep</code> option. (<a href="#kb-support-example-1">view example 1</a>)</p>

		<p>Changing the <code>keyboardMultiplier</code> option will multiply the default step by the set amount.
			This is useful when dealing with larger value ranges but smaller step size. (<a href="#kb-support-example-2">view example 2</a>)</p>

		<p>The Page Up and Page Down keys can be used to make larger steps through the slider range. By default, the page keys
            multiply the default step by 5. This can be changed using the <code>keyboardPageMultiplier</code> option. (<a href="#kb-support-example-2">view example 2</a>)</p>

		<h3 id="kb-support-example-1"><br>Example 1</h3>

		<p>
			Here the keyboard step size is <code>20</code> which is equal to <code>range / keyboardDefaultStep</code>.
		</p>

        <div class="example overflow">
            <div id="slider-keyboard"></div>
			<p class="example-val" id="slider-keyboard-value"></p>
		</div>

		<h3 id="kb-support-example-2">Example 2</h3>

		<p>
			Here the keyboard step size is <code>50</code> which is equal to <code>step * keyboardMultiplier</code>.
		</p>

		<div class="example overflow">
			<div id="slider-keyboard-2"></div>
			<p class="example-val" id="slider-keyboard-2-value"></p>
        </div>

		<?php run('keyboard-support'); ?>
		<?php run('keyboard-support-link'); ?>

        <div class="options">
			<strong>keyboardSupport</strong>
            <strong>Default</strong>
            <div><code>true</code></div>
            <strong>Accepted values</strong>
            <div><code>true</code>, <code>false</code></div>
        </div>
    </div>

    <div class="side">
        <?php code('keyboard-support'); ?>

		<div class="viewer-header">Show the slider value</div>

		<div class="viewer-content">
			<?php code('keyboard-support-link'); ?>
		</div>
    </div>

</section>


<?php sect('document-element'); ?>
<h2>Document Element</h2>

<section>

    <div class="view">

        <p>When moving the slider through documents, or in other advanced scenarios, the <code>documentElement</code> that events are bound to can be changed.</p>

        <p>See <a href="https://github.com/leongersen/noUiSlider/pull/821">this pull request</a> for more information.</p>

        <div class="options">
            <strong>Default</strong>
            <div>The document the slider is in</div>

            <strong>Accepted values</strong>
            <div>Any other document element</div>
        </div>
    </div>

    <div class="side">

        <?php code('document-element'); ?>
    </div>

</section>
