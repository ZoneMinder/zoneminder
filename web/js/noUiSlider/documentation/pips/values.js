var pipsValues = document.getElementById('pips-values');

noUiSlider.create(pipsValues, {
    range: {
        'min': [     0 ],
        '10%': [   500,  500 ],
        '50%': [  4000, 1000 ],
        'max': [ 10000 ]
    },
    start: 0,
    pips: {
        mode: 'values',
        values: [50, 552, 2251, 3200, 5000, 7080, 9000],
        density: 4
    }
});
