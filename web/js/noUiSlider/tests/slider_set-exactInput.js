// #436
QUnit.test("Set & SetHandle exactInput parameter", function (assert) {

    document.getElementById('qunit-fixture').innerHTML = '<div class="slider"></div>';

    var slider = document.getElementById('qunit-fixture').querySelector('.slider');

    var newInput = document.createElement('input');
    newInput.id = 'slider-input';
    document.getElementById('qunit-fixture').insertBefore(newInput, slider);

    noUiSlider.create(slider, {
        start: 3000,
        range: {
            'min': [0, 1000],
            'max': [10000]
        },
        tooltips: true
    });

    newInput.addEventListener('change', function(event) {
        var value = parseFloat(event.target.value);
        slider.noUiSlider.set(value, true, true);
    });

    slider.noUiSlider.on('change', function (value) {
        if (newInput.value !== value){
            newInput.value = value;
        }
    });

    assert.equal(slider.noUiSlider.get(), 3000);

    slider.noUiSlider.set(5000);
    assert.equal(slider.noUiSlider.get(), 5000);

    slider.noUiSlider.set(4600);
    assert.equal(slider.noUiSlider.get(), 5000);

    slider.noUiSlider.set(1200, true, true);
    assert.equal(slider.noUiSlider.get(), 1200);

    slider.noUiSlider.set(6789.99, true, true);
    assert.equal(slider.noUiSlider.get(), 6789.99);

    slider.noUiSlider.set(2200, true, null);
    assert.equal(slider.noUiSlider.get(), 2000);

    slider.noUiSlider.set(300, true, undefined);
    assert.equal(slider.noUiSlider.get(), 0);

    slider.noUiSlider.setHandle(0, 8000);
    assert.equal(slider.noUiSlider.get(), 8000);

    slider.noUiSlider.setHandle(0, 3400);
    assert.equal(slider.noUiSlider.get(), 3000);

    slider.noUiSlider.setHandle(0, 5400, true, true);
    assert.equal(slider.noUiSlider.get(), 5400);

    slider.noUiSlider.setHandle(0, 7777.77, true, true);
    assert.equal(slider.noUiSlider.get(), 7777.77);

    slider.noUiSlider.setHandle(0, 3200, true, null);
    assert.equal(slider.noUiSlider.get(), 3000);

    slider.noUiSlider.setHandle(0, 1800, true, undefined);
    assert.equal(slider.noUiSlider.get(), 2000);

    newInput.value = 8888.99;
    slider.noUiSlider.on('change.one', function (value) {
        assert.equal(value, 8888.99);
    });

    newInput.value = "321.54";
    slider.noUiSlider.on('change.two', function (value) {
        assert.equal(value, 321.54);
    });
});
