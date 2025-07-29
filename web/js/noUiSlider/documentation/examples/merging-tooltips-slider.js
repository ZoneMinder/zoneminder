var mergingTooltipSlider = document.getElementById('merging-tooltips');

noUiSlider.create(mergingTooltipSlider, {
    start: [20, 32, 50, 70, 80, 90],
    connect: true,
    tooltips: [false, true, true, true, true, true],
    range: {
        'min': 0,
        'max': 100
    }
});

mergeTooltips(mergingTooltipSlider, 15, ' - ');
