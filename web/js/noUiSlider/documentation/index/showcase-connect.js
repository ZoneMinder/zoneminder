// Give the slider dimensions
range.style.height = '400px';
range.style.margin = '0 auto 30px';

var valuesDivs = [
    document.getElementById('range-value-1'),
    document.getElementById('range-value-2'),
    document.getElementById('range-value-3'),
    document.getElementById('range-value-4')
];

var diffDivs = [
    document.getElementById('range-diff-1'),
    document.getElementById('range-diff-2'),
    document.getElementById('range-diff-3')
];

// When the slider value changes, update the input and span
range.noUiSlider.on('update', function (values, handle) {
    valuesDivs[handle].innerHTML = values[handle];
    diffDivs[0].innerHTML = values[1] - values[0];
    diffDivs[1].innerHTML = values[2] - values[1];
    diffDivs[2].innerHTML = values[3] - values[2];
});
