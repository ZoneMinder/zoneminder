var skipSlider = document.getElementById('skipstep');

noUiSlider.create(skipSlider, {
    range: {
        'min': 0,
        '10%': 10,
        '20%': 20,
        '30%': 30,
        // Nope, 40 is no fun.
        '50%': 50,
        '60%': 60,
        '70%': 70,
        // I never liked 80.
        '90%': 90,
        'max': 100
    },
    snap: true,
    start: [20, 90]
});
