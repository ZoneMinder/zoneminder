var smoothStepsSlider = document.getElementById('smooth-steps');
var smoothStepsValues = document.getElementById('smooth-steps-values');

noUiSlider.create(smoothStepsSlider, {
    start: [0, 100],
    behaviour: 'smooth-steps',
    step: 15,
    connect: true,
    range: {
        'min': 0,
        'max': 100
    }
});

smoothStepsSlider.noUiSlider.on('update', function (values) {
    smoothStepsValues.innerHTML = values.join(' :: ');
});
