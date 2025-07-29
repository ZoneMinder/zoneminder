QUnit.test("Value setting/getting", function (assert) {

    document.getElementById('qunit-fixture').innerHTML = '<div class="slider"></div>';

    var slider = document.getElementById('qunit-fixture').querySelector('.slider');

    noUiSlider.create(slider, {
        range: {min: -30, max: 1080},
        start: [0, 10],
        behaviour: 'drag',
        connect: true,
        format: {
            to: function (x) {
                return x.toFixed(1);
            },
            from: Number
        }
    });

    assert.deepEqual(slider.noUiSlider.get(), ["0.0", "10.0"]);

    slider.noUiSlider.set([-10, 80]);
    assert.deepEqual(slider.noUiSlider.get(), ["-10.0", "80.0"]);

    slider.noUiSlider.set(5);
    assert.deepEqual(slider.noUiSlider.get(), ["5.0", "80.0"]);

    slider.noUiSlider.set([10, 980.51]);
    assert.deepEqual(slider.noUiSlider.get(), ["10.0", "980.5"]);

    slider.noUiSlider.set([null]);
    assert.deepEqual(slider.noUiSlider.get(), ["10.0", "980.5"]);

    slider.noUiSlider.set([null, 80]);
    assert.deepEqual(slider.noUiSlider.get(), ["10.0", "80.0"]);

    slider.noUiSlider.set([10.6]);
    assert.deepEqual(slider.noUiSlider.get(), ["10.6", "80.0"]);

    slider.noUiSlider.set([null, 10.6]);
    assert.deepEqual(slider.noUiSlider.get(), ["10.6", "10.6"]);

    slider.noUiSlider.set([30, null]);
    assert.deepEqual(slider.noUiSlider.get(), ["30.0", "30.0"]);

    slider.noUiSlider.set(null);
    assert.deepEqual(slider.noUiSlider.get(), ["30.0", "30.0"]);

    slider.noUiSlider.set(false);
    assert.deepEqual(slider.noUiSlider.get(), ["30.0", "30.0"]);

    slider.noUiSlider.setHandle(0, 20);
    assert.deepEqual(slider.noUiSlider.get(), ["20.0", "30.0"]);

    slider.noUiSlider.setHandle(1, 40);
    assert.deepEqual(slider.noUiSlider.get(), ["20.0", "40.0"]);

    slider.noUiSlider.setHandle(1, 15);
    assert.deepEqual(slider.noUiSlider.get(), ["20.0", "20.0"]);

    slider.noUiSlider.setHandle(1, null);
    assert.deepEqual(slider.noUiSlider.get(), ["20.0", "20.0"]);

});
