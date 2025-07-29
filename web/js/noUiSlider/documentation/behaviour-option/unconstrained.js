var unconstrainedSlider = document.getElementById('unconstrained');
var unconstrainedValues = document.getElementById('unconstrained-values');

noUiSlider.create(unconstrainedSlider, {
    start: [20, 50, 80],
    behaviour: 'unconstrained-tap',
    connect: true,
    range: {
        'min': 0,
        'max': 100
    }
});

unconstrainedSlider.noUiSlider.on('update', function (values) {
    unconstrainedValues.innerHTML = values.join(' :: ');
});
