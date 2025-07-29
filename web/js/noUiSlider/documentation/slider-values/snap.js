var snapSlider = document.getElementById('slider-snap');

noUiSlider.create(snapSlider, {
    start: [0, 500],
    snap: true,
    connect: true,
    range: {
        'min': 0,
        '10%': 50,
        '20%': 100,
        '30%': 150,
        '40%': 500,
        '50%': 800,
        'max': 1000
    }
});
