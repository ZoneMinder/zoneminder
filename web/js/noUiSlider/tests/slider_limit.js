QUnit.test("Limit option", function (assert) {

    document.getElementById('qunit-fixture').innerHTML = '<div class="slider"></div>';

    var settings = {
        start: [50, 100],
        limit: 30,
        range: {
            'min': 30,
            'max': 986
        }
    };

    var slider = document.getElementById('qunit-fixture').querySelector('.slider');

    noUiSlider.create(slider, settings);

    assert.deepEqual(slider.noUiSlider.get(), ['50.00', '80.00'], 'Limit applied on init.');

    slider.noUiSlider.set([null, 600]);
    assert.deepEqual(slider.noUiSlider.get(), ['50.00', '80.00'], 'Handle can\'t leave limit.');

    slider.noUiSlider.set([150, 600]);
    assert.deepEqual(slider.noUiSlider.get(), ['150.00', '180.00'], 'Multiple set limit.');

    // Rebuild with new settings;
    settings.direction = 'rtl';
    slider.noUiSlider.destroy();

    noUiSlider.create(slider, settings);

    assert.deepEqual(slider.noUiSlider.get(), ['50.00', '80.00'], 'RTL correct value.');

    slider.noUiSlider.set([120, 240]);
    assert.deepEqual(slider.noUiSlider.get(), ['120.00', '150.00'], 'RTL set.');
});

QUnit.test("Limit option (non-linear)", function (assert) {

    document.getElementById('qunit-fixture').innerHTML = '<div class="slider"></div>';

    var settings = {
        start: [50, 310],
        limit: 100,
        range: {
            'min': [0],
            '20%': [160],
            '40%': [240],
            '60%': [280],
            '80%': [300],
            'max': [310]
        }
    };

    var slider = document.getElementById('qunit-fixture').querySelector('.slider');

    noUiSlider.create(slider, settings);

    assert.deepEqual(slider.noUiSlider.get(), ['50.00', '150.00'], 'Limit applied on init.');

    slider.noUiSlider.set([null, 600]);
    assert.deepEqual(slider.noUiSlider.get(), ['50.00', '150.00'], 'Handle can\'t leave limit.');

    slider.noUiSlider.set([150, 310]);
    assert.deepEqual(slider.noUiSlider.get(), ['150.00', '250.00'], 'Multiple set limit.');

    // Rebuild with new settings;
    settings.direction = 'rtl';
    slider.noUiSlider.destroy();

    noUiSlider.create(slider, settings);

    assert.deepEqual(slider.noUiSlider.get(), ['50.00', '150.00'], 'RTL correct value.');

    slider.noUiSlider.set([120, 240]);
    assert.deepEqual(slider.noUiSlider.get(), ['120.00', '220.00'], 'RTL set.');
});
