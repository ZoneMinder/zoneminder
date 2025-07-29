var limitFieldMin = document.getElementById('slider-limit-value-min');
var limitFieldMax = document.getElementById('slider-limit-value-max');

limitSlider.noUiSlider.on('update', function (values, handle) {
    (handle ? limitFieldMax : limitFieldMin).innerHTML = values[handle];
});
