<?php sect('formatting'); ?>
<h1>Number formatting</h1>

<section>

    <div class="view">

        <p>noUiSlider has a <code>format</code> option to configure both incoming and outgoing formatting.</p>

        <p>In this example, the slider format uses <strong>no decimals</strong>. The <code>tooltips</code> use <strong>one decimal</strong>.</p>

        <p>The <code>from</code> function takes a formatted value to parse. It gets a string and should return a number.</p>

        <p>The <code>to</code> function takes a number to format. It gets a number and should return a string.</p>

        <p>noUiSlider can optionally be used with the <code>wNumb</code> library for formatting. See <a href="/nouislider/number-formatting/">the documentation on number formatting</a> for more details.</p>

        <div class="example">
            <div id="formatting-slider"></div>
            <span class="example-val" id="formatting-start"></span>
            <span class="example-val" id="formatting-end"></span>
            <?php run('formatting-format'); ?>
            <?php run('formatting'); ?>
        </div>
    </div>

    <div class="side">
        <?php code('formatting-format'); ?>

        <div class="viewer-header">Slider with formatting</div>

        <div class="viewer-content">
            <?php code('formatting'); ?>
        </div>
    </div>
</section>
