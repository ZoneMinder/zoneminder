<?php sect('merging-tooltips'); ?>
<h1>Merging overlapping tooltips</h1>

<section>

    <div class="view">
        <p><a href="https://github.com/leongersen/noUiSlider/issues/1032">Issue #1032</a> asks to merge overlapping tooltips. As this feature is outside the scope of the tooltips-feature in noUiSlider, this example can be used to implement this feature using the event system.</p>

        <div class="example">
            <div id="merging-tooltips"></div>
            <?php run('merging-tooltips'); ?>
            <?php run('merging-tooltips-slider'); ?>
        </div>
    </div>

    <div class="side">
        <div class="viewer-header">Initializing the slider</div>

        <div class="viewer-content">
            <?php code('merging-tooltips-slider'); ?>
        </div>

        <div class="viewer-header">Merging overlapping tooltips</div>

        <div class="viewer-content">
            <?php code('merging-tooltips'); ?>
        </div>
    </div>
</section>
