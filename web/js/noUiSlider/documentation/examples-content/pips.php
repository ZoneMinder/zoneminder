<?php sect('pips'); ?>
<h1>Interacting with pips</h1>

<section>

	<div class="view">
		<p>When using the <a href="/nouislider/pips/">pips feature</a>, several interactions can be added. Pips can also be styled.</p>

        <p>This example shows pips being displayed vertically, and highlighted when the slider handle is at that value.</p>

        <p>Have a look at the <a href="/nouislider/examples/#section-click-pips">example on moving the slider by clicking pips</a>.</p>

		<div class="example" style="padding-bottom: 80px">
			<div id="slider-pips"></div>
			<?php run('pips'); ?>
			<?php run('pips-events'); ?>
		</div>
	</div>

	<div class="side">

		<div class="viewer-header">Slider with pips</div>

		<div class="viewer-content">
			<?php code('pips'); ?>
		</div>

        <?php code('pips-events'); ?>

		<div class="viewer-header">Vertical Pips</div>

		<div class="viewer-content">
            <?php loadShowCSS('pips'); ?>
		</div>
	</div>
</section>
