// Set the slider value to 20
document.getElementById('write-button').addEventListener('click', function () {
    slider.noUiSlider.set(20);
});

// Read the slider value.
document.getElementById('read-button').addEventListener('click', function () {
    alert(slider.noUiSlider.get());
});
