var arbitraryValuesSlider = document.getElementById('arbitrary-values-slider');
var arbitraryValuesForSlider = ['128MB', '256MB', '1GB', '8GB', '16GB', '32GB'];

var format = {
    to: function(value) {
        return arbitraryValuesForSlider[Math.round(value)];
    },
    from: function (value) {
        return arbitraryValuesForSlider.indexOf(value);
    }
};

noUiSlider.create(arbitraryValuesSlider, {
    // start values are parsed by 'format'
    start: ['1GB', '16GB'],
    range: { min: 0, max: arbitraryValuesForSlider.length - 1 },
    step: 1,
    tooltips: true,
    format: format,
    pips: { mode: 'steps', format: format, density: 50 },
});
