var connectSlider = document.getElementById('slider-connect');

noUiSlider.create(connectSlider, {
    start: 40,
    connect: 'lower',
    range: {
        'min': 0,
        'max': 100
    }
});
