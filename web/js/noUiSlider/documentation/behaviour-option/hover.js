var hoverSlider = document.getElementById('hover');
var field = document.getElementById('hover-val');

noUiSlider.create(hoverSlider, {
    start: 20,
    behaviour: 'hover-snap',
    direction: 'rtl',
    range: {
        'min': 0,
        'max': 10
    }
});

hoverSlider.noUiSlider.on('hover', function (value) {
    field.innerHTML = value;
});
