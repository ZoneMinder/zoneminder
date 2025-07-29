var formatSlider = document.getElementById('formatting-slider');

noUiSlider.create(formatSlider, {
    // Values are parsed as numbers using the "from" function in "format"
    start: ['20.0', '80.0'],
    range: {
        'min': 0,
        'max': 100
    },
    format: formatForSlider,
    tooltips: {
        // tooltips are output only, so only a "to" is needed
        to: function(numericValue) {
            return numericValue.toFixed(1);
        }
    }
});

// Values are parsed as numbers using the "from" function in "format"
formatSlider.noUiSlider.set(['25.666', '57.66']);

var formatValues = [
    document.getElementById('formatting-start'),
    document.getElementById('formatting-end')
];

formatSlider.noUiSlider.on('update', function (values, handle, unencoded) {
    // "values" has the "to" function from "format" applied
    // "unencoded" contains the raw numerical slider values
    formatValues[handle].innerHTML = values[handle] + '<br><strong>No format:</strong> ' + unencoded[handle];
});
