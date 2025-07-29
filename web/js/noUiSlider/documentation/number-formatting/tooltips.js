var tooltipSlider = document.getElementById('slider-tooltips');

noUiSlider.create(tooltipSlider, {
    start: [20, 80, 120, 140],
    tooltips: [
        // no tooltip
        false,
        // tooltip with custom formatting
        wNumb({decimals: 1}),
        // tooltip with default formatting
        true,
        // tooltip with manual formatting
        { to: function(value) { return '❤️ ' + value; } }
    ],
    /**
     * Tooltip with default formatting on all handles:
     * tooltips: true,
     *
     * Tooltip with specific formatting on all handles:
     * tooltips: {
     *     to: ...
     * }
     *
     * Tooltip with specific formatting on each handle:
     * tooltips: [
     *     { to: ... },
     *     { to: ... },
     *     { to: ... },
     * ]
     */
    range: {
        'min': 0,
        'max': 200
    }
});
