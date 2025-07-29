var slider = document.getElementById('slider-events');
var setter = document.getElementById('setter');
var inputLog = document.getElementById('input-log');

noUiSlider.create(slider, {
    start: [0, 10],
    behaviour: 'drag-tap',
    connect: [false, true, false],
    range: {
        'min': [0],
        'max': [20]
    }
});
