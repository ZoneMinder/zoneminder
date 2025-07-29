var tapSlider = document.getElementById('tap');

noUiSlider.create(tapSlider, {
    start: 40,
    behaviour: 'tap',
    connect: [false, true],
    range: {
        'min': 20,
        'max': 80
    }
});
