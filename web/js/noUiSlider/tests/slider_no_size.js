QUnit.test("Test init with min = max", function (assert) {

    document.getElementById('qunit-fixture').innerHTML = '<div class="slider"></div>';

    var slider = document.getElementById('qunit-fixture').querySelector('.slider');

    noUiSlider.create(slider, {
        start: [0, 0, 0],
        range: {
            'min': 0,
            'max': 0
        }
    });

    assert.deepEqual(slider.noUiSlider.getPositions(), [0, 50, 100]);
});
