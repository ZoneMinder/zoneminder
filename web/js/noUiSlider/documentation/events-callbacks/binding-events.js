var lUpdate = document.getElementById('l-update');
var lSlide = document.getElementById('l-slide');
var lDrag = document.getElementById('l-drag');
var lSet = document.getElementById('l-set');
var lChange = document.getElementById('l-change');
var lStart = document.getElementById('l-start');
var lEnd = document.getElementById('l-end');

slider.noUiSlider.on('update', function () {
    addClassFor(lUpdate, 'tShow', 450);
});

slider.noUiSlider.on('slide', function () {
    addClassFor(lSlide, 'tShow', 450);
});

slider.noUiSlider.on('drag', function () {
    addClassFor(lDrag, 'tShow', 450);
});

slider.noUiSlider.on('set', function () {
    addClassFor(lSet, 'tShow', 450);
});

slider.noUiSlider.on('change', function () {
    addClassFor(lChange, 'tShow', 450);
});

slider.noUiSlider.on('start', function () {
    addClassFor(lStart, 'tShow', 450);
});

slider.noUiSlider.on('end', function () {
    addClassFor(lEnd, 'tShow', 450);
});
