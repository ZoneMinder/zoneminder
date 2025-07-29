var verticalSlider = document.getElementById('slider-vertical');

noUiSlider.create(verticalSlider, {
    start: 40,
    orientation: 'vertical',
    range: {
        'min': 0,
        'max': 100
    }
});
