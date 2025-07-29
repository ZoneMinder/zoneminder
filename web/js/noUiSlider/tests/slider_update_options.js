QUnit.test("Update options", function (assert) {

    document.getElementById('qunit-fixture').innerHTML = '<div class="slider"></div>';

    var slider = document.getElementById('qunit-fixture').querySelector('.slider');

    noUiSlider.create(slider, {
        start: 20,
        range: {
            'min': 0,
            'max': 100
        }
    });

    slider.noUiSlider.updateOptions({ start: 3 });
    assert.deepEqual(slider.noUiSlider.get(), "3.00");

    slider.noUiSlider.updateOptions({ start: 0 });
    assert.deepEqual(slider.noUiSlider.get(), "0.00");
});
