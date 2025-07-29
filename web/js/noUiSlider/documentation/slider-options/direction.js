var directionSlider = document.getElementById('slider-direction');

noUiSlider.create(directionSlider, {
    start: 20,
    direction: 'rtl',
    range: {
        'min': 0,
        'max': 100
    }
});
