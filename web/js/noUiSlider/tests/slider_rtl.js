QUnit.test("RTL slider multiple value set.", function (assert) {

    document.getElementById('qunit-fixture').innerHTML = '<div class="slider"></div>';

    var slider = document.getElementById('qunit-fixture').querySelector('.slider');

    noUiSlider.create(slider, {
        range: {min: 0.201, max: 1},
        step: 0.01,
        start: 0.401,
        direction: "rtl",
        orientation: "vertical",
        format: {
            to: function (x) {
                return x.toFixed(1);
            },
            from: Number
        }
    });

    assert.equal(slider.noUiSlider.get(), 0.4);

    slider.noUiSlider.set(0.201);
    assert.equal(slider.noUiSlider.get(), 0.2);

    slider.noUiSlider.set(0.201);
    assert.equal(slider.noUiSlider.get(), 0.2);
});
