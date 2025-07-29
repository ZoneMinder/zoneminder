var activePips = [null, null];

pipsSlider.noUiSlider.on('update', function (values, handle) {
    // Remove the active class from the current pip
    if (activePips[handle]) {
        activePips[handle].classList.remove('active-pip');
    }

    // Match the formatting for the pip
    var dataValue = Math.round(values[handle]);

    // Find the pip matching the value
    activePips[handle] = pipsSlider.querySelector('.noUi-value[data-value="' + dataValue + '"]');

    // Add the active class
    if (activePips[handle]) {
        activePips[handle].classList.add('active-pip');
    }
});
