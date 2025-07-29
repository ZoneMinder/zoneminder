var handlesSlider4 = document.getElementById('slider-handles4');

noUiSlider.create(handlesSlider4, {
    start: [4000, 8000, 12000, 16000],
    connect: [false, true, true, false, true],
    range: {
        'min': [2000],
        'max': [20000]
    }
});
