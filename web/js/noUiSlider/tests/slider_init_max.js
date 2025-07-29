QUnit.test("Test init of slider values near max", function (assert) {

    document.getElementById('qunit-fixture').innerHTML = '<div class="slider"></div>';

    var slider = document.getElementById('qunit-fixture').querySelector('.slider');
    var max = 10;

    noUiSlider.create(slider, {
        start: [max, max, max, max],
        step: 1,
        margin: 1,
        tooltips: true,
        pips: {mode: 'count', values: 5},
        range: {
            'min': 0,
            'max': max
        }
    });

    assert.deepEqual(slider.noUiSlider.get(), ['7.00', '8.00', '9.00', '10.00']);
});
