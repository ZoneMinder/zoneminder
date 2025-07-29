QUnit.test("Classes", function (assert) {

    document.getElementById('qunit-fixture').innerHTML = '<div class="slider"></div>';

    noUiSlider.cssClasses.target += ' test';

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

    assert.equal(slider.className, 'slider noUi-target test noUi-ltr noUi-horizontal noUi-txt-dir-ltr');

    slider.noUiSlider.destroy();

    assert.equal(slider.className, 'slider');
});
