var slider = document.getElementById('vertical-ltr');

noUiSlider.create(slider, {
    start: [20, 80],
    connect: true,
    direction: "ltr",
    orientation: "vertical",
    range: {
        'min': 0,
        'max': 100
    }
});
