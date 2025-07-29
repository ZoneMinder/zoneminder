QUnit.test("Unordered slider range", function (assert) {

    document.getElementById('qunit-fixture').innerHTML = '<div class="slider"></div>';

    var slider = document.getElementById('qunit-fixture').querySelector('.slider');

    noUiSlider.create(slider, {
        start: [12],
        format: {
            to: function (x) {
                return Math.round(x).toString();
            }, from: Number
        },
        range: {
            '50%': 16,
            'min': 11,
            '10%': 12,
            'max': 20
        }
    });

    slider.noUiSlider.set(11);
    assert.equal(slider.noUiSlider.get(), 11);

    slider.noUiSlider.set(12);
    assert.equal(slider.noUiSlider.get(), 12);

    slider.noUiSlider.set(16);
    assert.equal(slider.noUiSlider.get(), 16);
});
