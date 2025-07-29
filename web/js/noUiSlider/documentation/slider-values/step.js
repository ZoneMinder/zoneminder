var stepSlider = document.getElementById('slider-step');

noUiSlider.create(stepSlider, {
    start: [4000],
    step: 1000,
    range: {
        'min': [2000],
        'max': [10000]
    }
});
