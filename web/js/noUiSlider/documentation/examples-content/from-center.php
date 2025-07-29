<?php sect('from-center'); ?>
<h1>Slider with connect from the center</h1>

<section>

    <div class="view">

        <p><a href="https://github.com/leongersen/noUiSlider/issues/371">Issue #371</a> requested a slider with the connect option originating from the slider center.
            This can be implemented by hiding one of the handles and using the <code>"unconstrained"</code> behaviour.</p>

        <div class="example">
            <div id="from-center"></div>
            <?php run('from-center'); ?>
            <?php run('from-center'); ?>
        </div>
    </div>

    <div class="side">

        <?php loadShowCSS('from-center'); ?>
        <?php code('from-center'); ?>
    </div>
</section>
