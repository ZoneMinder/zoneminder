var slider = document.getElementById('slider');

noUiSlider.create(slider, {
    start: [10, 90],
    documentElement: documentElement,
    range: {
        'min': 0,
        'max': 100
    }
});
