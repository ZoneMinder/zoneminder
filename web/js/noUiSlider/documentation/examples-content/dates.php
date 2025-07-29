<?php sect('dates'); ?>
<h1>Working with dates</h1>

<section>

	<div class="view">

		<p>This example shows how to convert dates to numerical ranges, and then use the <code>update</code> event to display them.</p>

		<div class="example">
			<div id="slider-date"></div>
			<span class="example-val" id="event-start"></span>
			<span class="example-val" id="event-end"></span>
			<?php run('date-setup'); ?>
			<?php run('date-event'); ?>
		</div>
	</div>

	<div class="side">

		<div class="viewer-header">Setup</div>

		<div class="viewer-content">
			<?php code('date-setup'); ?>
		</div>

		<div class="viewer-header">Update event</div>

		<div class="viewer-content">
			<?php code('date-event'); ?>
		</div>
	</div>
</section>
