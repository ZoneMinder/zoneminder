var html5Slider = document.getElementById('html5');

noUiSlider.create(html5Slider, {
    start: [10, 30],
    connect: true,
    range: {
        'min': -20,
        'max': 40
    }
});
