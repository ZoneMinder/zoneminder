var nonLinearSlider = document.getElementById('slider-non-linear');

noUiSlider.create(nonLinearSlider, {
    start: [4000],
    range: {
        'min': [2000],
        '30%': [4000],
        '70%': [8000],
        'max': [10000]
    }
});
