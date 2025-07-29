QUnit.test("Padding option", function (assert) {

    document.getElementById('qunit-fixture').innerHTML = '<div class="slider"></div>';

    var settings = {
        start: [0, 10],
        padding: 1,
        range: {
            'min': 0,
            'max': 10
        }
    };

    var slider = document.getElementById('qunit-fixture').querySelector('.slider');

    noUiSlider.create(slider, settings);

    assert.deepEqual(slider.noUiSlider.get(), ['1.00', '9.00'], 'Padding applied on init.');

    slider.noUiSlider.set([2, 10]);
    assert.deepEqual(slider.noUiSlider.get(), ['2.00', '9.00'], 'Handle can\'t pass padding.');

    slider.noUiSlider.set([0, 10]);
    assert.deepEqual(slider.noUiSlider.get(), ['1.00', '9.00'], 'Multiple set outside of padding');

    // Rebuild with new settings;
    settings.direction = 'rtl';
    slider.noUiSlider.destroy();

    noUiSlider.create(slider, settings);

    assert.deepEqual(slider.noUiSlider.get(), ['1.00', '9.00'], 'RTL on init.');

    slider.noUiSlider.set([3, 10]);
    assert.deepEqual(slider.noUiSlider.get(), ['3.00', '9.00'], 'RTL set.');
});

QUnit.test("Padding option (non-linear)", function (assert) {

    document.getElementById('qunit-fixture').innerHTML = '<div class="slider"></div>';

    var settings = {
        start: [0, 310],
        padding: 20,
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

    assert.deepEqual(slider.noUiSlider.get(), ['20.00', '290.00'], 'Padding applied on init.');

    slider.noUiSlider.set([40, 310]);
    assert.deepEqual(slider.noUiSlider.get(), ['40.00', '290.00'], 'Handle can\'t pass padding.');

    slider.noUiSlider.set([0, 270]);
    assert.deepEqual(slider.noUiSlider.get(), ['20.00', '270.00'], 'Multiple set outside of padding');

    // Rebuild with new settings;
    settings.direction = 'rtl';
    slider.noUiSlider.destroy();

    noUiSlider.create(slider, settings);

    assert.deepEqual(slider.noUiSlider.get(), ['20.00', '290.00'], 'RTL on init.');

    slider.noUiSlider.set([20, 285]);
    assert.deepEqual(slider.noUiSlider.get(), ['20.00', '285.00'], 'RTL set.');
});

QUnit.test("Padding option", function (assert) {

    document.getElementById('qunit-fixture').innerHTML = '<div class="slider"></div>';

    var settings = {
        start: [0, 100],
        padding: [10, 5],
        range: {
            'min': 0,
            'max': 100
        }
    };

    var slider = document.getElementById('qunit-fixture').querySelector('.slider');

    noUiSlider.create(slider, settings);

    assert.deepEqual(slider.noUiSlider.get(), ['10.00', '95.00'], 'Different paddings applied');

    // =============

    slider.noUiSlider.destroy();

    settings.padding = [0, 10];

    noUiSlider.create(slider, settings);
    assert.deepEqual(slider.noUiSlider.get(), ['0.00', '90.00'], 'One of the padding values is 0');

});

QUnit.test("Padding option (non-linear)", function (assert) {

    document.getElementById('qunit-fixture').innerHTML = '<div class="slider"></div>';

    var settings = {
        start: [0, 310],
        padding: [20, 5],
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

    assert.deepEqual(slider.noUiSlider.get(), ['20.00', '305.00'], 'Different paddings applied');

    // =============

    slider.noUiSlider.destroy();

    settings.padding = [0, 10];

    noUiSlider.create(slider, settings);
    assert.deepEqual(slider.noUiSlider.get(), ['0.00', '300.00'], 'One of the padding values is 0');

});

QUnit.test("Padding values <= 100%", function (assert) {

    document.getElementById('qunit-fixture').innerHTML = '<div class="slider"></div>';

    var settings = {
        start: [0, 100],
        padding: [30, 70],
        range: {
            'min': 0,
            'max': 100
        }
    };

    var slider = document.getElementById('qunit-fixture').querySelector('.slider');

    noUiSlider.create(slider, settings);

    assert.deepEqual(slider.noUiSlider.get(), ['30.00', '30.00'], 'Slider values can only be 30');

});


QUnit.test("Padding values <= 100% (non-linear)", function (assert) {

    document.getElementById('qunit-fixture').innerHTML = '<div class="slider"></div>';

    var settings = {
        start: [0, 310],
        padding: [155, 155],
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

    assert.deepEqual(slider.noUiSlider.get(), ['155.00', '155.00'], 'Slider values can only be 155');

});
