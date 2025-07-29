var valuesSlider = document.getElementById('values-slider');
var valuesForSlider = [1,2,3,4,5,6,7,8,10,12,14,16,20,24,28,32]; // 16 values

var format = {
    to: function(value) {
        return valuesForSlider[Math.round(value)];
    },
    from: function (value) {
        return valuesForSlider.indexOf(Number(value));
    }
};

noUiSlider.create(valuesSlider, {
    start: [8, 24],
    // A linear range from 0 to 15 (16 values)
    range: { min: 0, max: valuesForSlider.length - 1 },
    // steps of 1
    step: 1,
    tooltips: true,
    format: format,
    pips: { mode: 'steps', format: format },
});

// The display values can be used to control the slider
valuesSlider.noUiSlider.set(['7', '28']);
