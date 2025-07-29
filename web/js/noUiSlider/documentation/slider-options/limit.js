var limitSlider = document.getElementById('slider-limit');

noUiSlider.create(limitSlider, {
    start: [10, 120],
    limit: 40,
    behaviour: 'drag',
    connect: true,
    range: {
        'min': 0,
        'max': 100
    }
});
