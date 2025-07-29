var connectSlider2 = document.getElementById('slider-connect2');

noUiSlider.create(connectSlider2, {
    start: [20, 40, 60],
    connect: [true, false, true, true],
    range: {
        'min': 0,
        'max': 80
    }
});
