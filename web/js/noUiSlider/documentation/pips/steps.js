function filterPips(value, type) {
    if (type === 0) {
        return value < 2000 ? -1 : 0;
    }
    return value % 1000 ? 2 : 1;
}

var pipsSteps = document.getElementById('pips-steps');

noUiSlider.create(pipsSteps, {
    range: {
        'min': [     0 ],
        '10%': [   500,  500 ],
        '50%': [  4000, 1000 ],
        'max': [ 10000 ]
    },
    start: 0,
    pips: {
        mode: 'steps',
        density: 3,
        filter: filterPips,
        format: wNumb({
            decimals: 2,
            prefix: '&euro;'
        })
    }
});
