var slider = document.getElementById('slider');

noUiSlider.create(slider, {
    start: [80],
    range: {
        'min': [0],
        'max': [100]
    }
});
