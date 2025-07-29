var sliderFormat = document.getElementById('slider-format');

noUiSlider.create(sliderFormat, {
    start: [20000],
    step: 1000,
    range: {
        'min': [20000],
        'max': [80000]
    },
    format: wNumb({
        decimals: 3,
        thousand: '.',
        suffix: ' (US $)'
    })
});
