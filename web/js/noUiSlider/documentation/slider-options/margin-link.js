var marginMin = document.getElementById('slider-margin-value-min'),
    marginMax = document.getElementById('slider-margin-value-max');

marginSlider.noUiSlider.on('update', function (values, handle) {
    if (handle) {
        marginMax.innerHTML = values[handle];
    } else {
        marginMin.innerHTML = values[handle];
    }
});
