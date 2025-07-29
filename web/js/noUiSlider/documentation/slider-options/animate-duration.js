var animatedSlider = document.getElementById('slider-slow');

noUiSlider.create(animatedSlider, {
    animate: true,
    animationDuration: 1600,
    start: 20,
    range: {
        min: 0,
        max: 100
    }
});
