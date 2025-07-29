var pipsSlider = document.getElementById('slider-pips');

noUiSlider.create(pipsSlider, {
    start: [20, 80],
    margin: 20,
    connect: true,
    range: {
        'min': 0,
        'max': 100
    },
    step: 20,
    pips: {
        mode: 'steps',
        density: 3
    }
});
