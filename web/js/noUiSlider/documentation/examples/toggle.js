var toggleSlider = document.getElementById('slider-toggle');

noUiSlider.create(toggleSlider, {
    orientation: "vertical",
    start: 0,
    range: {
        'min': [0, 1],
        'max': 1
    },
    format: wNumb({
        decimals: 0
    })
});

toggleSlider.noUiSlider.on('update', function (values, handle) {
    if (values[handle] === '1') {
        toggleSlider.classList.add('off');
    } else {
        toggleSlider.classList.remove('off');
    }
});
