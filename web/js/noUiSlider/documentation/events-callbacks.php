<?php
	$title = "noUiSlider - Events and callbacks";
	$description = "Listen to everything that happens, and run your code when you want too. noUiSlider has a bunch of events and callbacks. Read all about them!";
    $canonical = "nouislider/events-callbacks/"
?>

<h1>Events</h1>

<section>

	<div class="view-more">

		<p>noUiSlider offers several ways to listen to interaction: <code>'update'</code>, <code>'change'</code>, <code>'set'</code>, <code>'slide'</code> and <code>'drag'</code>. These events can all be used at the same time. There are also the <code>'start'</code> and <code>'end'</code> events, that fire when a drag is started or ended.</p>

		<p>Events always fire in the following order:</p>

		<p><code>'start'</code> &gt; <code>'slide'</code> &gt; <code>'drag'</code> &gt; <code>'update'</code> &gt; <code>'change'</code> &gt; <code>'set'</code> &gt; <code>'end'</code></p>

	</div>

	<div class="side">

		<div class="example">

			<div id="slider-events"></div>

			<div class="logger">
				<div id="l-update">Update</div>
				<div id="l-slide">Slide</div>
				<div id="l-drag">Drag</div>
				<div id="l-set">Set</div>
				<div id="l-change">Change</div>
				<div id="l-start">Start</div>
				<div id="l-end">End</div>
			</div>

			<div class="logger">
				<input id="input-log">
				<button id="setter">.set([ 5, 15 ])</button>
			</div>

			<?php run('class'); ?>
			<?php run('demo'); ?>
			<?php run('buttoninput'); ?>
			<?php run('binding-events'); ?>
		</div>
	</div>
</section>


<section>

	<table class="data-table events-table">
		<thead>
			<tr>
				<th>&nbsp;</th>
                <th width="80">Start</th>
                <th width="80">Slide</th>
                <th width="80">Drag</th>
                <th width="80">Update</th>
                <th width="80">Change</th>
                <th width="80">Set</th>
				<th width="80">End</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><span>A handle is <strong>activated</strong>, starting dragging</span></td>
                <td class="events-yes" title="Start"><span>Yes</span></td>
                <td class="events-no" title="Slide"><span>No</span></td>
                <td class="events-no" title="Drag"><span>No</span></td>
                <td class="events-no" title="Update"><span>No</span></td>
                <td class="events-no" title="Change"><span>No</span></td>
                <td class="events-no" title="Set"><span>No</span></td>
				<td class="events-no" title="End"><span>No</span></td>
			</tr>
			<tr>
				<td><span>A handle is <strong>released</strong> after dragging</span></td>
                <td class="events-no" title="Start"><span>No</span></td>
                <td class="events-no" title="Slide"><span>No</span></td>
                <td class="events-no" title="Drag"><span>No</span></td>
                <td class="events-no" title="Update"><span>No</span></td>
                <td class="events-yes" title="Change"><span>Yes</span></td>
                <td class="events-yes" title="Set"><span>Yes</span></td>
				<td class="events-yes" title="End"><span>Yes</span></td>
			</tr>
			<tr>
				<td><span>A handle moves while <strong>dragging</strong></span></td>
                <td class="events-no" title="Start"><span>No</span></td>
                <td class="events-yes" title="Slide"><span>Yes</span></td>
                <td class="events-no" title="Drag"><span>No</span></td>
                <td class="events-yes" title="Update"><span>Yes</span></td>
                <td class="events-no" title="Change"><span>No</span></td>
                <td class="events-no" title="Set"><span>No</span></td>
				<td class="events-no" title="End"><span>No</span></td>
			</tr>
			<tr>
				<td><span>A connect moves while <strong>dragging</strong></span></td>
                <td class="events-no" title="Start"><span>No</span></td>
                <td class="events-yes" title="Slide"><span>Yes</span></td>
                <td class="events-yes" title="Drag"><span>Yes</span></td>
                <td class="events-yes" title="Update"><span>Yes</span></td>
                <td class="events-no" title="Change"><span>No</span></td>
                <td class="events-no" title="Set"><span>No</span></td>
				<td class="events-no" title="End"><span>No</span></td>
			</tr>
			<tr>
				<td><span>The <strong><code>.set()</code></strong> method is called</span></td>
                <td class="events-no" title="Start"><span>No</span></td>
                <td class="events-no" title="Slide"><span>No</span></td>
                <td class="events-no" title="Drag"><span>No</span></td>
                <td class="events-yes" title="Update"><span>Yes</span></td>
                <td class="events-no" title="Change"><span>No</span></td>
                <td class="events-yes" title="Set"><span>Yes</span></td>
				<td class="events-no" title="End"><span>No</span></td>
			</tr>
			<tr>
				<td><span>When bound using the <strong><code>.on()</code></strong> method</span></td>
                <td class="events-no" title="Start"><span>No</span></td>
                <td class="events-no" title="Slide"><span>No</span></td>
                <td class="events-no" title="Drag"><span>No</span></td>
                <td class="events-yes" title="Update"><span>Yes</span></td>
                <td class="events-no" title="Change"><span>No</span></td>
                <td class="events-no" title="Set"><span>No</span></td>
				<td class="events-no" title="End"><span>No</span></td>
			</tr>
            <tr>
                <td><span>A slider is moved by <strong>tapping</strong> it</span></td>
                <td class="events-no" title="Start"><span>No</span></td>
                <td class="events-yes" title="Slide"><span>Yes</span></td>
                <td class="events-no" title="Drag"><span>No</span></td>
                <td class="events-yes" title="Update"><span>Yes</span></td>
                <td class="events-yes" title="Change"><span>Yes</span></td>
                <td class="events-yes" title="Set"><span>Yes</span></td>
                <td class="events-no" title="End"><span>No</span></td>
            </tr>
			<tr>
				<td><span>A handle is moved by arrow keys</span></td>
                <td class="events-no" title="Start"><span>No</span></td>
                <td class="events-yes" title="Slide"><span>Yes</span></td>
                <td class="events-no" title="Drag"><span>No</span></td>
                <td class="events-yes" title="Update"><span>Yes</span></td>
                <td class="events-yes" title="Change"><span>Yes</span></td>
                <td class="events-yes" title="Set"><span>Yes</span></td>
				<td class="events-no" title="End"><span>No</span></td>
			</tr>
		</tbody>
	</table>
</section>


<?php sect('binding'); ?>
<h2>Binding</h2>

<section>

	<div class="view">
		<p>noUiSlider uses a custom binding model with support for namespaces. There are two methods, <code>.on(eventName, callback)</code> and <code>.off(eventName)</code>. Events can be namespaced by appending a period ('.') and an identifier to the event name.</p>

		<p>Nested namespaces (<code>'slide.something.else'</code>) are not supported, and are threated as a single namespace (so <code>'.a.b'</code> isn't related to <code>'.a'</code>).</p>

		<p><code>values</code> is an array containing the current slider values, with formatting applied. <code>handle</code> is the index of the handle that caused the event, starting at zero. <code>values[handle]</code> gives the value for the handle that triggered the event.</p>

		<p><code>unencoded</code> contains the slider values without any formatting. </p>

		<p>For all events, <code>this</code> is set to the current slider's API, containing (among others) the <code>.get()</code> and <code>.set()</code> methods. The slider api is also available as the sixth argument to all events.</p>
	</div>

	<?php code('binding'); ?>

</section>


<?php sect('update'); ?>
<h2>Update</h2>

<section>

	<div class="view">
		<p>Use this event when synchronizing the slider value to another element, such as an <code>&lt;input&gt;</code>. It fires every time the slider values are <strong>changed</strong>, either by a user or by calling API methods. Additionally, it fires <strong>immediately</strong> when bound. In most cases, this event should be more convenient than the <code>'slide'</code> event.</p>
	</div>
</section>


<?php sect('slide'); ?>
<h2>Slide</h2>

<section>

	<div class="view">
		<p>This event is useful to specifically listen to a handle being dragged, while <strong>ignoring other updates</strong> to the slider value. This event also fires on a change by a 'tap'. In most cases, the <code>'update'</code> is the better choice.</p>
	</div>
</section>

<?php sect('drag'); ?>
<h2>Drag</h2>

<section>

	<div class="view">
		<p>Use this event to listen for when a connect element between handles is being dragged, while <strong>ignoring other updates</strong> to the slider values.</p>

		<p>The handle in the callback is the first handle connected to the dragged connect.</p>
	</div>
</section>

<?php sect('set'); ?>
<h2>Set</h2>

<section>

	<div class="view">
		<p>Whenever a slider is changed to a new value, this event is fired. This function will trigger every time a slider <em>stops</em> changing, <strong>including</strong> after calls to the <code>.set()</code> method. This event can be considered as the 'end of slide'.</p>
	</div>

</section>


<?php sect('change'); ?>
<h2>Change</h2>

<section>

	<div class="view">
		<p>This event is similar to the <code>'change'</code> events on regular <code>&lt;input&gt;</code> elements. It fires when a user <em>stops</em> sliding, when a slider value is changed by 'tap', or on keyboard interaction.</p>
	</div>
</section>


<?php sect('start'); ?>
<h2>Start</h2>

<section>

	<div class="view">
		<p>This event fires when a handle is clicked (<code>mousedown</code>, or the equivalent touch events).</p>
	</div>
</section>


<?php sect('end'); ?>
<h2>End</h2>

<section>

	<div class="view">
		<p>This event is the opposite of the <code>'start'</code> event. If fires when a handle is released (<code>mouseup</code> etc), or when a slide is canceled due to other reasons (such as mouse cursor leaving the browser window).</p>
	</div>
</section>
