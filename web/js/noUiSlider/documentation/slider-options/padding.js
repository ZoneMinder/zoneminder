var paddingSlider = document.getElementById('slider-padding');

noUiSlider.create(paddingSlider, {
    start: [20, 80],
    padding: [10, 15], // Or just 10
    range: {
        'min': 0,
        'max': 100
    }
});
