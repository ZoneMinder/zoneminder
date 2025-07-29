QUnit.test("Slider with drag all behaviour", function (assert) {

    document.getElementById('qunit-fixture').innerHTML = '<div id="slider1" style="position: fixed; left: 10px; top: 10px;"></div>';

    var slider = document.getElementById('slider1');

    noUiSlider.create(slider, {
        start: [40, 60, 80],
        behaviour: 'drag-all',
        connect: true,
        step: 1,
        range: {
            'min': 20,
            'max': 100
        },
        animate: false,
        animationDuration: 0
    });

    assert.deepEqual(slider.noUiSlider.get(), ['40.00', '60.00', '80.00']);

    var rect = slider.getBoundingClientRect();
    var midX = rect.x + rect.width / 2;
    var midY = rect.y + rect.height / 2;

    simulateMousedown(document.elementFromPoint(midX - 20, midY), midX - 20, midY);
    simulateMousemove(document.documentElement, midX + 20, midY);

    assert.deepEqual(slider.noUiSlider.get(), ['51.00', '71.00', '91.00'], 'All handles moved together');
});
