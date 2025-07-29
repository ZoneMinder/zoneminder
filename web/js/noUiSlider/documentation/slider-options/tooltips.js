var tooltipSlider = document.getElementById('slider-tooltips');

noUiSlider.create(tooltipSlider, {
    start: [20, 80, 120],
    tooltips: [false, wNumb({decimals: 1}), true],
    range: {
        'min': 0,
        'max': 200
    }
});

// Remove tooltips:
// tooltipSlider.noUiSlider.removeTooltips();
