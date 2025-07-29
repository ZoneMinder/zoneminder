var startSlider = document.getElementById('slider-start');

noUiSlider.create(startSlider, {
    start: [20, 80],
    range: {
        'min': [0],
        'max': [100]
    }
});
