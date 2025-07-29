var slider = document.getElementById('vertical-rtl');

noUiSlider.create(slider, {
    start: [20, 80],
    connect: true,
    direction: "rtl",
    orientation: "vertical",
    range: {
        'min': 0,
        'max': 100
    }
});
