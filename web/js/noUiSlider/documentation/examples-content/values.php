<?php sect('values'); ?>
<h1>Using an array of values</h1>

<section>

    <div class="view">

        <p>noUiSlider allows <a href="/nouislider/slider-values/#section-non-linear">configuration of complex non-linear ranges</a> with various constraints on positioning and stepping. This setup might be overly complicated to display a slider with a pre-determined available selection of values.</p>

        <p>For such a scenario, the <code>format</code> option can be used. This option provides a wrapper around the <code>get</code> and <code>set</code> methods, allowing for a simpler interface.</p>

        <div class="example">
            <div id="values-slider" style="margin-bottom: 60px"></div>
            <?php run('values'); ?>
        </div>

        <div class="example">
            <div id="arbitrary-values-slider" style="margin-bottom: 60px"></div>
            <?php run('values-arbitrary'); ?>
        </div>
    </div>

    <div class="side">
        <?php code('values'); ?>

        <div class="viewer-header">What not to do</div>

        <div class="viewer-content">
            <?php code('values-not-recommended'); ?>
        </div>

        <div class="viewer-header">Using arbitrary (string) values</div>

        <div class="viewer-content">
            <?php code('values-arbitrary'); ?>
        </div>
    </div>
</section>
