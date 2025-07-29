var snapSlider = document.getElementById('snap');

noUiSlider.create(snapSlider, {
    start: 40,
    behaviour: 'snap',
    connect: [true, false],
    range: {
        'min': 20,
        'max': 80
    }
});
