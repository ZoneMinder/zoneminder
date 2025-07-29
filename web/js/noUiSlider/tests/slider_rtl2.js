QUnit.test("RTL slider 2", function (assert) {

    document.getElementById('qunit-fixture').innerHTML = '<div class="slider"></div>';

    var slider = document.getElementById('qunit-fixture').querySelector('.slider');

    noUiSlider.create(slider, {
        range: {
            'min': 0,
            'max': 50
        },
        start: [10, 40],
        behaviour: 'tap',
        connect: true,
        direction: 'rtl'
    });

    assert.deepEqual(slider.noUiSlider.get(), ["10.00", "40.00"], "Proper start handling in RTL.");

    slider.noUiSlider.set([20, 30]);
    assert.deepEqual(slider.noUiSlider.get(), ["20.00", "30.00"]);

    slider.noUiSlider.set([40, 45]);
    assert.deepEqual(slider.noUiSlider.get(), ["40.00", "45.00"], "RTL slider overstepped properly.");

    slider.noUiSlider.set([30, 35]);
    assert.deepEqual(slider.noUiSlider.get(), ["30.00", "35.00"], "RTL slider understepped properly.");

});
