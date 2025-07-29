var stepSlider = document.getElementById('slider-step');

noUiSlider.create(stepSlider, {
    start: [20, 80],
    step: 10,
    range: {
        'min': 0,
        'max': 100
    }
});
