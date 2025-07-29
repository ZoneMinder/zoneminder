noUiSlider.create(slider, {
    start: [10, 120],
    handleAttributes: [
        { 'aria-label': 'lower' },
        { 'aria-label': 'upper' },
    ],
    range: {
        'min': 0,
        'max': 100
    }
});
