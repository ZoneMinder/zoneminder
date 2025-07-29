<?php sect('hiding-tooltips'); ?>
<h1>Only showing tooltips when sliding handles</h1>

<section>

	<div class="view">

		<p><a href="https://github.com/leongersen/noUiSlider/issues/836">Issue #836</a> requested a way to toggle tooltips after slider creation. This effect can be achieved by using the <code>.noUi-active</code> class to show and hide the tooltips. No additional JavaScript is involved.</p>

		<div class="example">
			<div class="slider" id="slider-hide"></div>

			<?php run('hiding-tooltips'); ?>
		</div>
	</div>

	<div class="side">

		<?php loadShowCSS('hiding-tooltips'); ?>
	</div>
</section>
