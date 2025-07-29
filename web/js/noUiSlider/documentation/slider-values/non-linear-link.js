var nonLinearSliderValueElement = document.getElementById('slider-non-linear-value');

// Show the value for the *last* moved handle.
nonLinearSlider.noUiSlider.on('update', function (values, handle) {
    nonLinearSliderValueElement.innerHTML = values[handle];
});
