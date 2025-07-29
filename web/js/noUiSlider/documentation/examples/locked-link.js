function setLockedValues() {
    lockedValues = [
        Number(slider1.noUiSlider.get()),
        Number(slider2.noUiSlider.get())
    ];
}

slider1.noUiSlider.on('change', setLockedValues);
slider2.noUiSlider.on('change', setLockedValues);

slider1.noUiSlider.on('slide', function (values, handle) {
    crossUpdate(values[handle], slider2);
});

slider2.noUiSlider.on('slide', function (values, handle) {
    crossUpdate(values[handle], slider1);
});
