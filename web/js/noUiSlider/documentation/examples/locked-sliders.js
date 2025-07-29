noUiSlider.create(slider1, {
    start: 60,

    // Disable animation on value-setting,
    // so the sliders respond immediately.
    animate: false,
    range: {
        min: 50,
        max: 100
    }
});

noUiSlider.create(slider2, {
    start: 80,
    animate: false,
    range: {
        min: 50,
        max: 100
    }
});

slider1.noUiSlider.on('update', function (values, handle) {
    slider1Value.innerHTML = values[handle];
});

slider2.noUiSlider.on('update', function (values, handle) {
    slider2Value.innerHTML = values[handle];
});
