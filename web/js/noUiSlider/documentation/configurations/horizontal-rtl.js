var slider = document.getElementById('horizontal-rtl');

noUiSlider.create(slider, {
    start: [20, 80],
    connect: true,
    direction: "rtl",
    orientation: "horizontal",
    range: {
        'min': 0,
        'max': 100
    }
});
