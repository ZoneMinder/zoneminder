var keyboardSupportValue = document.getElementById('slider-keyboard-value');
keyboardSupport.noUiSlider.on('update', function (values, handle) {
    keyboardSupportValue.innerHTML = values[handle];
});

var keyboardSupport2Value = document.getElementById('slider-keyboard-2-value');
keyboardSupport2.noUiSlider.on('update', function (values, handle) {
    keyboardSupport2Value.innerHTML = values[handle];
});
