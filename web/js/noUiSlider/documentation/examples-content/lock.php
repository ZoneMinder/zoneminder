<?php sect('lock'); ?>
<h1>Locking sliders together</h1>

<section>

	<div class="view">

		<p>Two cross-updating sliders can be created using a combination of the <code>change</code> and <code>slide</code> events.</p>

		<div class="example">
			<div class="slider" id="slider1"></div>
			<span class="example-val" id="slider1-span"></span>

			<div class="slider" id="slider2"></div>
			<span class="example-val" id="slider2-span"></span>

			<button id="lockbutton" style="float: right; margin: 20px 0 0;">Lock</button>

			<?php run('locked-setup'); ?>
			<?php run('locked-sliders'); ?>
			<?php run('locked-link'); ?>
			<?php run('locked-crossupdate'); ?>
		</div>
	</div>

	<div class="side">

		<div class="viewer-header">Setup and button clicks</div>

		<div class="viewer-content">
			<?php code('locked-setup'); ?>
		</div>

		<div class="viewer-header">The <code>Crossupdate</code> function</div>

		<div class="viewer-content">
			<?php code('locked-crossupdate'); ?>
		</div>

		<div class="viewer-header">Initializing the sliders</div>

		<div class="viewer-content">
			<?php code('locked-sliders'); ?>
		</div>

		<div class="viewer-header">Linking the sliders together</div>

		<div class="viewer-content">
			<?php code('locked-link'); ?>
		</div>
	</div>
</section>
