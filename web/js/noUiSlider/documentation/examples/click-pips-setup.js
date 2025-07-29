var clickPipsSlider = document.getElementById('slider-click-pips');

noUiSlider.create(clickPipsSlider, {
    range: {
        min: 0,
        max: 100
    },
    start: [50],
    pips: {mode: 'count', values: 5}
});
