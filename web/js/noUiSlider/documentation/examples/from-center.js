var fromCenterSlider = document.getElementById('from-center');

noUiSlider.create(fromCenterSlider, {
    start: [50, 80],
    behaviour: 'unconstrained',
    connect: true,
    range: {
        'min': 0,
        'max': 100
    }
});
