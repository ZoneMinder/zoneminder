<?php sect('styling'); ?>
<h1>Styling</h1>

<section>

    <div class="view">
        <p>noUiSlider, by default, comes with a design with large handles and box shadows. In general, larger handles make range sliders easier to use on mobile devices and touch screens.</p>

        <p>It is recommended to use the default stylesheet, overriding where necessary, as a starting point when re-styling noUiSlider.</p>

        <p>When the slider handles are restyled to be smaller, <code>.noUi-touch-area</code> should be styled to be larger, to be easier to click or touch. The sliders in this example show the larger touch area when hovered.</p>

        <p><code>padding</code> can be added to <code>.noUi-target</code> to contain handles within the slider's width.</p>

        <p>See <a href="/nouislider/more/#section-styling">the documentation on styling</a> for further tips and an overview of classes.</p>

        <div class="example">
            <strong class="style-head">Default styling</strong>
            <div style="margin-bottom: 30px;" id="slider"></div>
            <strong class="style-head">Fit handles within slider</strong>
            <div style="margin-bottom: 30px;" id="slider-fit"></div>
            <strong class="style-head">Don't overlap handles</strong>
            <div style="margin-bottom: 30px;" id="slider-no-overlap"></div>
            <strong class="style-head">Rounded styling</strong>
            <div style="margin-bottom: 30px;" class="slider-styled" id="slider-round"></div>
            <strong class="style-head">Square styling</strong>
            <div class="slider-styled" id="slider-square"></div>
            <?php run('styling'); ?>
        </div>
    </div>

    <div class="side">

<pre><code>
&lt;div id=&quot;slider&quot;&gt;&lt;/div&gt;
&lt;div id=&quot;slider-fit&quot;&gt;&lt;/div&gt;
&lt;div id=&quot;slider-no-overlap&quot;&gt;&lt;/div&gt;
&lt;div class=&quot;slider-styled&quot; id=&quot;slider-round&quot;&gt;&lt;/div&gt;
&lt;div class=&quot;slider-styled&quot; id=&quot;slider-square&quot;&gt;&lt;/div&gt;
</code></pre>

        <div class="viewer-header">Don't overlap handles</div>

        <div class="viewer-content">
            <?php loadShowCSS('styling-no-overlap'); ?>
        </div>

        <div class="viewer-header">Fit handles withing the slider</div>

        <div class="viewer-content">
            <?php loadShowCSS('styling-fit'); ?>
        </div>

        <div class="viewer-header">Style resets</div>

        <div class="viewer-content">
            <?php loadShowCSS('styling'); ?>
        </div>

        <div class="viewer-header">Styling the touch area</div>

        <div class="viewer-content">
            <?php loadShowCSS('styling-touch-area'); ?>
        </div>

        <div class="viewer-header">Styling round slider</div>

        <div class="viewer-content">
            <?php loadShowCSS('styling-round'); ?>
        </div>

        <div class="viewer-header">Styling square slider</div>

        <div class="viewer-content">
            <?php loadShowCSS('styling-square'); ?>
        </div>
    </div>
</section>
