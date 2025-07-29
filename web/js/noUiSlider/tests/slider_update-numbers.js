QUnit.test("RTL single handle handleNumber", function (assert) {

    assert.expect(12);

    document.getElementById('qunit-fixture').innerHTML = '<div class="slider"></div><div class="slider"></div><div class="slider"></div><div class="slider"></div>';

    var sliders = document.getElementById('qunit-fixture').getElementsByClassName('slider');


    // --

    noUiSlider.create(sliders[0], {
        start: 20,
        range: {
            'min': 0,
            'max': 100
        }
    });

    sliders[0].noUiSlider.on('update', function (values, handle) {
        assert.equal(handle, 0, 'Handle number for single ltr');
        assert.deepEqual(values, ['20.00']);
    });

    // --

    noUiSlider.create(sliders[1], {
        start: 20,
        direction: 'rtl',
        range: {
            'min': 0,
            'max': 100
        }
    });

    sliders[1].noUiSlider.on('update', function (values, handle) {
        assert.equal(handle, 0, 'Handle number for single rtl');
        assert.deepEqual(values, ['20.00']);
    });

    // --

    var countSlider2Updates = 0;

    noUiSlider.create(sliders[2], {
        start: [20, 80],
        range: {
            'min': 0,
            'max': 100
        }
    });

    sliders[2].noUiSlider.on('update', function (values, handle) {
        assert.equal(handle, countSlider2Updates, 'Handle number for double ltr: ' + countSlider2Updates);
        assert.deepEqual(values, ['20.00', '80.00']);
        countSlider2Updates++;
    });

    // --

    var countSlider3Updates = 0;

    noUiSlider.create(sliders[3], {
        start: [20, 80],
        direction: 'rtl',
        range: {
            'min': 0,
            'max': 100
        }
    });

    sliders[3].noUiSlider.on('update', function (values, handle) {
        assert.equal(handle, countSlider3Updates);
        assert.deepEqual(values, ['20.00', '80.00']);
        countSlider3Updates++;
    });
});
