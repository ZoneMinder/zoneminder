var behaviourSlider = document.getElementById('behaviour');

noUiSlider.create(behaviourSlider, {
    start: [20, 40],
    step: 10,
    behaviour: 'drag',
    connect: true,
    range: {
        'min': 20,
        'max': 80
    }
});
