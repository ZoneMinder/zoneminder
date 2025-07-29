QUnit.test("Aria", function (assert) {

    document.getElementById('qunit-fixture').innerHTML = '<div class="slider"></div>';

    var slider = document.getElementById('qunit-fixture').querySelector('.slider');

    noUiSlider.create(slider, {
        start: [50, 150],
        connect: true,
        margin: 50,
        ariaFormat: {
            to: function (x) {
                return Math.round(x).toString();
            }, from: Number
        },
        handleAttributes: [
            { 'aria-label': 'lower' },
            { 'aria-label': 'upper' },
        ],
        range: {
            'min': 50,
            'max': 1050
        }
    });

    var handle0 = slider.querySelector('[data-handle="0"]');
    var handle1 = slider.querySelector('[data-handle="1"]');

    assert.equal(handle0.getAttribute('role'), 'slider');

    assert.equal(handle0.getAttribute('aria-label'), 'lower');
    assert.equal(handle0.getAttribute('aria-valuemin'), '50.0', 'Handle0 min');
    assert.equal(handle0.getAttribute('aria-valuemax'), '100.0', 'Handle0 max');
    assert.equal(handle0.getAttribute('aria-valuenow'), '50.0', 'Handle0 now');
    assert.equal(handle0.getAttribute('aria-valuetext'), '50', 'Handle0 txt');

    assert.equal(handle1.getAttribute('aria-label'), 'upper');
    assert.equal(handle1.getAttribute('aria-valuemin'), '100.0', 'Handle1 min');
    assert.equal(handle1.getAttribute('aria-valuemax'), '1050.0', 'Handle1 max');
    assert.equal(handle1.getAttribute('aria-valuenow'), '150.0', 'Handle1 now');
    assert.equal(handle1.getAttribute('aria-valuetext'), '150', 'Handle1 txt');

});
