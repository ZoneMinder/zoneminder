var pipsSlider = document.getElementById('slider-pips');

noUiSlider.create(pipsSlider, {
    start: [0, 90],
    step: 30,
    range: {
        'min': 0,
        'max': 210
    },
    pips: {
        mode: 'steps',
        density: 3,
        format: wNumb({
            decimals: 2,
            prefix: '€'
        })
    }
});
