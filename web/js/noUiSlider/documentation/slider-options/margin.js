var marginSlider = document.getElementById('slider-margin');

noUiSlider.create(marginSlider, {
    start: [20, 80],
    margin: 30,
    range: {
        'min': 0,
        'max': 100
    }
});
