var button1 = document.getElementById('update-1');
var button2 = document.getElementById('update-2');
var button3 = document.getElementById('update-3');
var button4 = document.getElementById('update-4');
var button5 = document.getElementById('update-5');
var button6 = document.getElementById('update-6');

button1.addEventListener('click', function () {
    updateSlider.noUiSlider.updateOptions({
        range: {
            'min': 20,
            'max': 50
        }
    });
});

button2.addEventListener('click', function () {
    updateSlider.noUiSlider.updateOptions({
        range: {
            'min': 10,
            'max': 40
        }
    });
});

button3.addEventListener('click', function () {
    updateSlider.noUiSlider.updateOptions({
        tooltips: true,
        pips: null
    });
});

button4.addEventListener('click', function () {
    updateSlider.noUiSlider.updateOptions({
        tooltips: false,
        pips: {
            mode: 'range',
            density: 3
        }
    });
});

button5.addEventListener('click', function () {
    updateSlider.noUiSlider.updateOptions({
        padding: 10,
    });
});

button6.addEventListener('click', function () {
    updateSlider.noUiSlider.updateOptions({
        padding: null,
    });
});
