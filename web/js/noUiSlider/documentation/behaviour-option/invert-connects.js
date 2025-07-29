var invertConnectsSlider = document.getElementById('invert-connects');

noUiSlider.create(invertConnectsSlider, {
    start: [20*60, 8*60],
    behaviour: 'unconstrained-invert-connects',
    step: 15,
    connect: true,
    range: {
        'min': 0,
        'max': 24*60
    }
});
