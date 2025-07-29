<?php
$title = "noUiSlider - Option Reference";
$description = "Full reference of all options, methods, properties and events in noUiSlider.";
$canonical = "nouislider/reference/"
?>

<section>

    <?php sect('options'); ?>
    <h1>Options</h1>

    <div class="double">

        <table class="reference-table">
            <thead>
            <tr>
                <th>Option</th>
                <th>Value</th>
                <th>Required</th>
                <th>Default</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><a href="/nouislider/slider-values/#section-handles">start</a></td>
                <td>array of <code>"string"</code> or <code>number</code></td>
                <td><code>true</code></td>
                <td><em>[none]</em></td>
            </tr>
            <tr>
                <td><a href="/nouislider/slider-values/#section-range">range</a></td>
                <td><code>object</code>, see <a href="/nouislider/slider-values/#section-range">range</a></td>
                <td><code>true</code></td>
                <td><em>[none]</em></td>
            </tr>
            <tr>
                <td><a href="/nouislider/slider-options/#section-step">step</a></td>
                <td><code>number</code></td>
                <td><code>false</code></td>
                <td><em>[none]</em></td>
            </tr>
            <tr>
                <td><a href="/nouislider/slider-values/#section-snap">snap</a></td>
                <td><code>boolean</code></td>
                <td><code>false</code></td>
                <td><code>false</code></td>
            </tr>
            <tr>
                <td><a href="/nouislider/number-formatting/">format</a></td>
                <td><code>Formatter</code></td>
                <td><code>false</code></td>
                <td>Accept all numeric values, output two decimals</td>
            </tr>
            <tr>
                <td><a href="/nouislider/number-formatting/#section-aria">ariaFormat</a></td>
                <td><code>Formatter</code></td>
                <td><code>false</code></td>
                <td>Two decimals</td>
            </tr>
            <tr>
                <td><a href="/nouislider/slider-options/#section-connect">connect</a></td>
                <td><code>string</code>, <code>boolean</code>, array of <code>boolean</code></td>
                <td><code>false</code></td>
                <td><code>false</code></td>
            </tr>
            <tr>
                <td><a href="/nouislider/slider-options/#section-margin">margin</a></td>
                <td><code>number</code></td>
                <td><code>false</code></td>
                <td><em>[none]</em></td>
            </tr>
            <tr>
                <td><a href="/nouislider/slider-options/#section-limit">limit</a></td>
                <td><code>number</code></td>
                <td><code>false</code></td>
                <td><em>[none]</em></td>
            </tr>
            <tr>
                <td><a href="/nouislider/slider-options/#section-padding">padding</a></td>
                <td><code>number</code></td>
                <td><code>false</code></td>
                <td><em>[none]</em></td>
            </tr>
            <tr>
                <td><a href="/nouislider/slider-options/#section-orientation">orientation</a></td>
                <td><code>string</code></td>
                <td><code>false</code></td>
                <td><code>"horizontal"</code></td>
            </tr>
            <tr>
                <td><a href="/nouislider/slider-options/#section-direction">direction</a></td>
                <td><code>string</code></td>
                <td><code>false</code></td>
                <td><code>"ltr"</code></td>
            </tr>
            <tr>
                <td><a href="/nouislider/slider-options/#section-tooltips">tooltips</a></td>
                <td><code>boolean</code>, <code>Formatter</code>, array of <code>boolean</code> or <code>Formatter</code> for each handle</td>
                <td><code>false</code></td>
                <td><code>false</code></td>
            </tr>
            <tr>
                <td><a href="/nouislider/slider-options/#section-animate">animate</a></td>
                <td><code>boolean</code></td>
                <td><code>false</code></td>
                <td><code>true</code></td>
            </tr>
            <tr>
                <td><a href="/nouislider/slider-options/#section-animate">animationDuration</a></td>
                <td><code>number</code></td>
                <td><code>false</code></td>
                <td>300</td>
            </tr>
            <tr>
                <td><a href="/nouislider/slider-options/#section-keyboard-support">keyboardSupport</a></td>
                <td><code>boolean</code></td>
                <td><code>false</code></td>
                <td><code>true</code></td>
            </tr>
            <tr>
                <td><a href="/nouislider/slider-options/#section-keyboard-support">keyboardDefaultStep</a></td>
                <td><code>number</code></td>
                <td><code>false</code></td>
                <td><code>10</code></td>
            </tr>
            <tr>
                <td><a href="/nouislider/slider-options/#section-keyboard-support">keyboardPageMultiplier</a></td>
                <td><code>number</code></td>
                <td><code>false</code></td>
                <td><code>5</code></td>
            </tr>
            <tr>
                <td><a href="/nouislider/slider-options/#section-keyboard-support">keyboardMultiplier</a></td>
                <td><code>number</code></td>
                <td><code>false</code></td>
                <td><code>1</code></td>
            </tr>
            <tr>
                <td><a href="/nouislider/slider-options/#section-handle-attributes">handleAttributes</a></td>
                <td>array of <code>{ key: value }</code> for each handle</td>
                <td><code>false</code></td>
                <td><em>[none]</em></td>
            </tr>
            <tr>
                <td><a href="/nouislider/behaviour-option/">behaviour</a></td>
                <td><code>string</code></td>
                <td><code>false</code></td>
                <td><code>"tap"</code></td>
            </tr>
            <tr>
                <td><a href="/nouislider/pips/">pips</a></td>
                <td><code>object</code>, see: <a href="/nouislider/pips/">pips</a></td>
                <td><code>false</code></td>
                <td><em>[none]</em></td>
            </tr>
            <tr>
                <td><a href="/nouislider/slider-options/#section-document-element">documentElement</a></td>
                <td><code>documentElement</code></td>
                <td><code>false</code></td>
                <td><code>ownerDocument</code></td>
            </tr>
            <tr>
                <td><a href="/nouislider/more/#section-styling">cssPrefix</a></td>
                <td><code>string</code></td>
                <td><code>false</code></td>
                <td><code>"noUi-"</code></td>
            </tr>
            <tr>
                <td><a href="/nouislider/more/#section-styling">cssClasses</a></td>
                <td><code>ClassList</code></td>
                <td><code>false</code></td>
                <td>(see code)</td>
            </tr>
            </tbody>
        </table>
    </div>
</section>

<section>

    <?php sect('methods'); ?>
    <h1>Methods</h1>

    <div class="double">
        <table class="reference-table">
            <thead>
            <tr>
                <th>Method</th>
                <th>Usage</th>
                <th>Arguments</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><a href="/nouislider/more/#section-update">destroy</a></td>
                <td><code>slider.noUiSlider.destroy()</code></td>
                <td><em>[none]</em></td>
            </tr>
            <tr>
                <td><a href="/nouislider/slider-values/#section-steps">steps</a></td>
                <td><code>slider.noUiSlider.steps()</code></td>
                <td><em>[none]</em></td>
            </tr>
            <tr>
                <td><a href="/nouislider/events-callbacks/#section-binding">on</a></td>
                <td><code>slider.noUiSlider.on(..., ...)</code></td>
                <td><code>"string"</code>, <code>function</code></td>
            </tr>
            <tr>
                <td><a href="/nouislider/events-callbacks/#section-binding">off</a></td>
                <td><code>slider.noUiSlider.off(...)</code></td>
                <td><code>"string"</code></td>
            </tr>
            <tr>
                <td><a href="/nouislider/slider-read-write/#section-reading">get</a></td>
                <td><code>slider.noUiSlider.get()</code></td>
                <td><em>[none]</em></td>
            </tr>
            <tr>
                <td><a href="/nouislider/slider-read-write/#section-setting">set</a></td>
                <td><code>slider.noUiSlider.set(...)</code></td>
                <td><em>[...]</em>, <code>boolean</code></td>
            </tr>
            <tr>
                <td><a href="/nouislider/slider-read-write/#section-setting">setHandle</a></td>
                <td><code>slider.noUiSlider.setHandle(..., ..., ...)</code></td>
                <td><code>"number"</code>, <code>"string"</code>, <code>boolean</code></td>
            </tr>
            <tr>
                <td><a href="/nouislider/slider-read-write/#section-setting">reset</a></td>
                <td><code>slider.noUiSlider.reset()</code></td>
                <td><em>[none]</em></td>
            </tr>
            <tr>
                <td><a href="/nouislider/more/#section-disable">disable</a></td>
                <td><code>slider.noUiSlider.disable()</code></td>
                <td><em>[none]</em>, <code>"number"</code></td>
            </tr>
            <tr>
                <td><a href="/nouislider/more/#section-disable">enable</a></td>
                <td><code>slider.noUiSlider.enable()</code></td>
                <td><em>[none]</em>, <code>"number"</code></td>
            </tr>
            <tr>
                <td><a href="/nouislider/more/#section-update">updateOptions</a></td>
                <td><code>slider.noUiSlider.updateOptions(...)</code></td>
                <td><em>object</em></td>
            </tr>
            <tr>
                <td><a href="/nouislider/pips/">pips</a></td>
                <td><code>slider.noUiSlider.pips(...)</code></td>
                <td><code>object</code>, see: <a href="/nouislider/pips/">pips</a></td>
            </tr>
            <tr>
                <td><a href="/nouislider/pips/">removePips</a></td>
                <td><code>slider.noUiSlider.removePips()</code></td>
                <td><em>[none]</em></td>
            </tr>
            <tr>
                <td><a href="/nouislider/slider-options/#section-tooltips">removeTooltips</a></td>
                <td><code>slider.noUiSlider.removeTooltips()</code></td>
                <td><em>[none]</em></td>
            </tr>
            <tr>
                <td><a href="/nouislider/slider-read-write/#section-positions">getPositions</a></td>
                <td><code>slider.noUiSlider.getPositions()</code></td>
                <td><em>[none]</em></td>
            </tr>
            </tbody>
        </table>
    </div>
</section>

<section>

    <?php sect('properties'); ?>
    <h1>Properties</h1>

    <div class="double">
        <table class="reference-table">
            <thead>
            <tr>
                <th>Property</th>
                <th>Usage</th>
                <th>Description</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><code>options</code></td>
                <td><code>slider.noUiSlider.options</code></td>
                <td>Reference to the options used to create the slider. <a href="/nouislider/more/#section-update">Documentation</a>.</td>
            </tr>
            <tr>
                <td><code>target</code></td>
                <td><code>slider.noUiSlider.target</code></td>
                <td>The slider element</td>
            </tr>
            <tr>
                <td><code>[disabled]</code></td>
                <td>As attribute</td>
                <td>Disable a slider or individual handles. <a href="/nouislider/more/#section-disable">Documentation</a>.</td>
            </tr>
            </tbody>
        </table>
    </div>
</section>

<section>

    <?php sect('events'); ?>
    <h1>Events</h1>

    <div class="double">
        <table class="reference-table">
            <thead>
            <tr>
                <th>Event</th>
                <th>Callback</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><a href="/nouislider/events-callbacks/#section-update">update</a></td>
                <td><a href="#event-callback"><em>Event Callback</em></a></td>
            </tr>
            <tr>
                <td><a href="/nouislider/events-callbacks/#section-slide">slide</a></td>
                <td><a href="#event-callback"><em>Event Callback</em></a></td>
            </tr>
            <tr>
                <td><a href="/nouislider/events-callbacks/#section-set">set</a></td>
                <td><a href="#event-callback"><em>Event Callback</em></a></td>
            </tr>
            <tr>
                <td><a href="/nouislider/events-callbacks/#section-change">change</a></td>
                <td><a href="#event-callback"><em>Event Callback</em></a></td>
            </tr>
            <tr>
                <td><a href="/nouislider/events-callbacks/#section-start">start</a></td>
                <td><a href="#event-callback"><em>Event Callback</em></a></td>
            </tr>
            <tr>
                <td><a href="/nouislider/events-callbacks/#section-end">end</a></td>
                <td><a href="#event-callback"><em>Event Callback</em></a></td>
            </tr>
            </tbody>
        </table>
    </div>
</section>

<section>

    <?php sect('event-callback'); ?>
    <h1>Event Callback</h1>

    <p>See <a href="/nouislider/events-callbacks/#section-binding">events</a> for more information.</p>

    <table class="reference-table">
        <thead>
        <tr>
            <th>Argument</th>
            <th>Type</th>
            <th>Description</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><code>this</code></td>
            <td><code>object</code></td>
            <td>Slider API</td>
        </tr>
        <tr>
            <td>values</td>
            <td>Array of <code>"string"</code></td>
            <td>As returned by <a href="/nouislider/slider-read-write/#section-reading">get</a></td>
        </tr>
        <tr>
            <td>handle</td>
            <td><code>number</code></td>
            <td>0-based index of handle that caused the event</td>
        </tr>
        <tr>
            <td>unencoded</td>
            <td>Array of <code>number</code></td>
            <td>Slider values without formatting applied</td>
        </tr>
        <tr>
            <td>tap</td>
            <td><code>boolean</code></td>
            <td>Whether the event was a tap</td>
        </tr>
        <tr>
            <td>positions</td>
            <td>Array of <code>number</code></td>
            <td>Handle positions, in percentages</td>
        </tr>
        <tr>
            <td>noUiSlider</td>
            <td><code>object</code></td>
            <td>Slider API</td>
        </tr>
        </tbody>
    </table>
</section>
