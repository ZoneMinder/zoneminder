QUnit.test("Keyboard support", function (assert) {

    function up(element) {
        element.dispatchEvent(new KeyboardEvent('keydown', {'key': 'ArrowUp'}));
    }

    function down(element) {
        element.dispatchEvent(new KeyboardEvent('keydown', {'key': 'ArrowDown'}));
    }

    function pageUp(element) {
        element.dispatchEvent(new KeyboardEvent('keydown', {'key': 'PageUp'}));
    }

    function pageDown(element) {
        element.dispatchEvent(new KeyboardEvent('keydown', {'key': 'PageDown'}));
    }

    document.getElementById('qunit-fixture').innerHTML = '<div class="slider"></div>';

    var slider = document.getElementById('qunit-fixture').querySelector('.slider');

    noUiSlider.create(slider, {
        start: [165],
        connect: true,
        range: {
            'min': 50,
            '10%': 100,
            '30%': [500, 50],
            '60%': [1000, 10],
            '90%': 1250,
            'max': 1300
        }
    });

    var handle0 = slider.querySelector('[data-handle="0"]');

    assert.equal(slider.noUiSlider.get(), '165.00');

    down(handle0);
    assert.equal(slider.noUiSlider.get(), '125.00');

    down(handle0);
    assert.equal(slider.noUiSlider.get(), '85.00');

    down(handle0);
    assert.equal(slider.noUiSlider.get(), '80.00');

    down(handle0);
    assert.equal(slider.noUiSlider.get(), '75.00');

    up(handle0);
    assert.equal(slider.noUiSlider.get(), '80.00');

    up(handle0);
    assert.equal(slider.noUiSlider.get(), '85.00');

    slider.noUiSlider.set(600);

    down(handle0);
    assert.equal(slider.noUiSlider.get(), '550.00');

    down(handle0);
    assert.equal(slider.noUiSlider.get(), '500.00');

    down(handle0);
    assert.equal(slider.noUiSlider.get(), '460.00');

    slider.noUiSlider.set(1000);

    down(handle0);
    assert.equal(slider.noUiSlider.get(), '950.00');

    up(handle0);
    assert.equal(slider.noUiSlider.get(), '1000.00');

    up(handle0);
    assert.equal(slider.noUiSlider.get(), '1010.00');

    slider.noUiSlider.set(1230);

    up(handle0);
    assert.equal(slider.noUiSlider.get(), '1240.00');

    up(handle0);
    assert.equal(slider.noUiSlider.get(), '1250.00');

    up(handle0);
    assert.equal(slider.noUiSlider.get(), '1255.00');

    slider.noUiSlider.set(55);

    down(handle0);
    assert.equal(slider.noUiSlider.get(), '50.00');

    slider.noUiSlider.destroy();

    noUiSlider.create(slider, {
        start: [1, 6],
        step: 3,
        range: {
            'min': 0,
            'max': 10
        }
    });

    assert.deepEqual(slider.noUiSlider.get(), ['0.00', '6.00']);

    handle0 = slider.querySelector('[data-handle="0"]');

    up(handle0);
    assert.deepEqual(slider.noUiSlider.get(), ['3.00', '6.00']);

    up(handle0);
    assert.deepEqual(slider.noUiSlider.get(), ['6.00', '6.00']);

    up(handle0);
    assert.deepEqual(slider.noUiSlider.get(), ['6.00', '6.00'], 'Handle 0 cannot push past handle 1');

    slider.noUiSlider.destroy();

    noUiSlider.create(slider, {
        start: [5],
        step: 1,
        range: {
            'min': 0,
            'max': 10
        },
        keyboardPageMultiplier: 2
    });

    assert.deepEqual(slider.noUiSlider.get(), '5.00');

    handle0 = slider.querySelector('[data-handle="0"]');

    pageDown(handle0);
    assert.deepEqual(slider.noUiSlider.get(), '3.00');

    pageUp(handle0);
    assert.deepEqual(slider.noUiSlider.get(), '5.00');
});
