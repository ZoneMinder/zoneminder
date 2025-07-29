// Change default classes for all sliders
// For example, all sliders will now have the className "noUi-target range-slider"
noUiSlider.cssClasses.target += ' range-slider';

noUiSlider.create(slider, {
    start: 80,
    range: {
        min: 0,
        max: 100
    },
    cssPrefix: 'noUi-', // defaults to 'noUi-',
    cssClasses: {
        // Full list of class names to override.
        // Does NOT extend the default classes.
        // The first class in String gets prefixed, the rest gets added as it is
    }
});
