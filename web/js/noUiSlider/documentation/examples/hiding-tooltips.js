var hidingTooltipSlider = document.getElementById('slider-hide');

noUiSlider.create(hidingTooltipSlider, {
    range: {
        min: 0,
        max: 100
    },
    start: [20, 80],
    tooltips: true
});
