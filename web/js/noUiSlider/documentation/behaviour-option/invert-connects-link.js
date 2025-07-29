var invertConnectsValues = document.getElementById('invert-connects-values');

invertConnectsSlider.noUiSlider.on('update', function (values) {
    var minToHHMM = function(mins) {
        var pad = function(n) { return ('0'+n).slice(-2); };
        return [mins / 60 ^ 0, mins % 60].map(pad).join(':');
    };
    invertConnectsValues.innerHTML = values.map(minToHHMM).join(' - ');
});
