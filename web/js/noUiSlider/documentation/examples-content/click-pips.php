<?php sect('click-pips'); ?>
<h1>Moving the slider by clicking pips</h1>

<section>

	<div class="view">

		<p><a href="https://github.com/leongersen/noUiSlider/issues/733">Issue #733</a> asks about clicking pips to move the slider to their value. All <code>.noUi-value</code> elements have a <code>data-value</code> attribute to facilitate this.</code></code></p>

		<div class="example">
			<div class="slider" id="slider-click-pips"></div>

			<?php run('click-pips-setup'); ?>
			<?php run('click-pips'); ?>
		</div>
	</div>

	<div class="side">

		<div class="viewer-header">Setup</div>

		<div class="viewer-content">
			<?php code('click-pips-setup'); ?>
		</div>

		<?php code('click-pips'); ?>
	</div>
</section>
