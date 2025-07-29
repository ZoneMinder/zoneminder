var dateSlider = document.getElementById('slider-date');

function timestamp(str) {
    return new Date(str).getTime();
}

noUiSlider.create(dateSlider, {
// Create two timestamps to define a range.
    range: {
        min: timestamp('2010'),
        max: timestamp('2016')
    },

// Steps of one week
    step: 7 * 24 * 60 * 60 * 1000,

// Two more timestamps indicate the handle starting positions.
    start: [timestamp('2011'), timestamp('2015')],

// No decimals
    format: wNumb({
        decimals: 0
    })
});
