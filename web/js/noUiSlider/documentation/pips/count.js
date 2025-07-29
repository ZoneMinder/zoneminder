var pipsCount = document.getElementById('pips-count');

noUiSlider.create(pipsCount, {
    range: {
        'min': [     0 ],
        '10%': [   500,  500 ],
        '50%': [  4000, 1000 ],
        'max': [ 10000 ]
    },
    start: 0,
    pips: {
        mode: 'count',
        values: 6,
        density: 4
    }
});
