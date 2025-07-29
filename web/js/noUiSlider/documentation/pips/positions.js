var pipsPositions = document.getElementById('pips-positions');

noUiSlider.create(pipsPositions, {
    range: {
        'min': [     0 ],
        '10%': [   500,  500 ],
        '50%': [  4000, 1000 ],
        'max': [ 10000 ]
    },
    start: 0,
    pips: {
        mode: 'positions',
        values: [0, 25, 50, 75, 100],
        density: 4
    }
});
