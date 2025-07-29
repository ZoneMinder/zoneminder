var directionField = document.getElementById('field');

directionSlider.noUiSlider.on('update', function (values, handle) {
    directionField.innerHTML = values[handle];
});
