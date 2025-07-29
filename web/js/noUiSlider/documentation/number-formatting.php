<?php
$title = "noUiSlider - Number formatting";
$description = "Number formatting in slider options, tooltips, ranges and pips.";
$canonical = "nouislider/number-formatting/"
?>

<h1>Number formatting</h1>

<section>

    <div class="view">

        <p>To format the slider output, noUiSlider offers a <code>format</code> option.</p>

        <p>Specify <code>to</code> and <code>from</code> functions to encode and decode the values.</p>

        <p>By default, noUiSlider will format output with <strong>2 decimals</strong>.</p>

        <p>The to/from paradigm is supported in the <a href="/wnumb">the wNumb formatting library</a>. wNumb offers a
            wide range of options and provides number validation. wNumb is completely optional, and not included in
            noUiSlider by default.</p>

        <p>Note that if the <code>.to()</code> method returns a <code>Number</code>, noUiSlider's <code>.get()</code>
            will also return <code>Number</code>s. See <a href="https://github.com/leongersen/noUiSlider/issues/813">this
                issue </a> for more details.

        <div class="example">
            <div id="slider-format"></div>
            <input title="Formatted number" id="input-format">
            <?php run('wnumb'); ?>
            <?php run('format-events'); ?>
        </div>
    </div>

    <div class="side">

        <?php code('format'); ?>

        <div class="viewer-header">Formatting with wNumb</div>

        <div class="viewer-content">
            <?php code('wnumb'); ?>
        </div>

        <div class="viewer-header">Connecting the input field</div>

        <div class="viewer-content">
            <?php code('format-events'); ?>
        </div>
    </div>

</section>


<?php sect('tooltips'); ?>
<h2>Tooltips</h2>

<section>
    <div class="view">
        <p>The <a href="/nouislider/slider-options/#section-tooltips"><code>tooltips</code></a> option can accept the
            same to/from formatter.</p>

        <p>By default, the tooltips use the same formatting as the slider output, but this can be overridden.</p>

        <div class="example overflow">
            <div id="slider-tooltips"></div>
            <?php run('tooltips'); ?>
        </div>
    </div>

    <div class="side">
        <?php code('tooltips'); ?>
    </div>
</section>



<?php sect('pips'); ?>
<h2>Pips</h2>

<section>
    <div class="view">
        <p>The <a href="/nouislider/pips/"><code>pips</code></a> option can accept the
            same to/from formatter.</p>

        <p>By default, the pips use the same formatting as the slider output, but this can be overridden.</p>

        <div class="example overflow">
            <div id="slider-pips"></div>
            <?php run('pips'); ?>
        </div>
    </div>

    <div class="side">
        <?php code('pips'); ?>
    </div>
</section>


<?php sect('aria'); ?>
<h2>Aria</h2>

<section>
    <div class="view">
        <p>Formatting can be used for the <code>aria-valuenow</code> accessibility attribute using the
            <code>ariaFormat</code> option.</p>
    </div>

    <div class="side">
        <?php code('aria'); ?>
    </div>
</section>
