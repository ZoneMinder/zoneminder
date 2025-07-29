var unAnimatedSlider = document.getElementById('slider-animate-false');
var setButton = document.getElementById('set-sliders');

noUiSlider.create(unAnimatedSlider, {
    animate: false,
    start: 20,
    range: {
        min: 0,
        max: 100
    }
});

setButton.addEventListener('click', function () {
    animatedSlider.noUiSlider.set(60);
    unAnimatedSlider.noUiSlider.set(60);
});
