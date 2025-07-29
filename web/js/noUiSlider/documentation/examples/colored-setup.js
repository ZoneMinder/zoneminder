var slider = document.getElementById('slider-color');

noUiSlider.create(slider, {
    start: [4000, 8000, 12000, 16000],
    connect: [false, true, true, true, true],
    range: {
        'min': [2000],
        'max': [20000]
    }
});
