var slider = document.getElementById('horizontal-ltr');

noUiSlider.create(slider, {
    start: [20, 80],
    connect: true,
    direction: "ltr",
    orientation: "horizontal",
    range: {
        'min': 0,
        'max': 100
    }
});
