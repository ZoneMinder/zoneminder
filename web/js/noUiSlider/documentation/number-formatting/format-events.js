var inputFormat = document.getElementById('input-format');

sliderFormat.noUiSlider.on('update', function (values, handle) {
    inputFormat.value = values[handle];
});

inputFormat.addEventListener('change', function () {
    sliderFormat.noUiSlider.set(this.value);
});
