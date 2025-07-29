var stepSliderValueElement = document.getElementById('slider-step-value');

stepSlider.noUiSlider.on('update', function (values, handle) {
    stepSliderValueElement.innerHTML = values[handle];
});
