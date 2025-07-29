<?php
	$title = "noUiSlider - JavaScript Range Slider";
	$description = "noUiSlider is a lightweight, ARIA-accessible JavaScript range slider with multi-touch and keyboard support. Great for responsive designs, and no dependencies!";
    $canonical = "nouislider/"
?>

<section>

	<div class="view">

		<h3 class="quotable">noUiSlider: lightweight JavaScript range slider with full touch support</h3>

		<ul class="pro-list">
            <li><strong>Accessible</strong> with ARIA and keyboard support</li>
            <li>Multi-Touch support on iOS, Android &amp; Windows devices</li>
            <li>GPU animated: no reflows, so fast; even on older devices</li>
            <li>Responsive design friendly</li>
			<li>No dependencies</li>
			<li>Tested in IE9 - IE11, Edge, Chrome, Firefox &amp; Safari</li>
		</ul>

		<a href="/nouislider/download/" class="button">Download noUiSlider</a>

		<p>noUiSlider is a <strong>lightweight</strong> range slider with multi-touch support and a ton of <strong>features</strong>. It supports non-linear ranges, requires no external dependencies, has keyboard support, and it works great in <strong>responsive designs</strong>. Have you tried this documentation on your phone?</p>

		<div class="example" id="showcase" style="margin: 0 20px;">

			<div id="range"></div>

			<div style="text-align: center; font-size: 12px;">
				<span id="range-value-4"></span> -
				<span id="range-diff-3" style="color: blue"></span> -
				<span id="range-value-3"></span> -
				<span id="range-diff-2" style="color: blue"></span> -
				<span id="range-value-2"></span> -
				<span id="range-diff-1" style="color: blue"></span> -
				<span id="range-value-1"></span>
			</div>

			<?php run('showcase'); ?>
			<?php run('showcase-connect'); ?>
		</div>
	</div>

	<div class="side">

		<?php code('showcase'); ?>

		<div class="viewer-header">Showing values</div>

		<div class="viewer-content">
			<?php code('showcase-connect'); ?>
		</div>
	</div>
</section>
