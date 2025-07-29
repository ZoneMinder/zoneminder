function filter500(value, type) {
    return value % 1000 ? 2 : 1;
}

function test_slider(pips, first) {

    document.getElementById('qunit-fixture').innerHTML = '<div class="slider"></div>';
    var slider = document.getElementById('qunit-fixture').querySelector('.slider');

    noUiSlider.create(slider, {
        range: {
            'min': [(first || 0)],
            '10%': [500, 500],
            '50%': [4000, 1000],
            'max': [10000]
        },
        start: 0,
        pips: pips
    });

    return slider;
}

// RANGE

QUnit.test("Pips: Range", function (assert) {

    var slider = test_slider({
        mode: 'range',
        density: 3,
        format: {
            to: function (value) {
                return value.toFixed(2);
            }
        }
    });

    assert.ok(document.getElementById('qunit-fixture').querySelectorAll('.noUi-pips').length, 'Pips where created');

    var markers = document.getElementById('qunit-fixture').querySelectorAll('.noUi-marker');
    assert.ok(markers.length >= 32 && markers.length <= 34, 'Density of 1/3 was applied');

    // Test formatter
    assert.equal(document.getElementById('qunit-fixture').querySelector('.noUi-value').innerHTML, '0.00');
});

QUnit.test("Pips: Steps", function (assert) {

    var slider = test_slider({
        mode: 'steps',
        density: 2,
        filter: filter500
    });

    // STEPS

    var markers = document.getElementById('qunit-fixture').querySelectorAll('.noUi-marker').length;
    assert.ok(markers >= 49 && markers <= 51, 'Density of 1/2 was applied');

});

QUnit.test("Pips: Positions", function (assert) {

    var slider = test_slider({
        mode: 'positions',
        values: [0, 25, 50, 75, 100]
    });

    // POSITIONS

    assert.equal(document.getElementById('qunit-fixture').querySelectorAll('.noUi-marker-large').length, 5, 'Large markers added for all values');
    assert.equal(document.getElementById('qunit-fixture').querySelectorAll('.noUi-value').length, 5);

    var pos = [];
    Array.prototype.forEach.call(document.getElementById('qunit-fixture').querySelectorAll('.noUi-value'), function (el) {
        pos.push(parseInt(el.style.left));
    });

    assert.deepEqual(pos, [0, 25, 50, 75, 100], 'Values placed on proper positions');

});

QUnit.test("Pips: Positions, stepped", function (assert) {

    assert.expect(0); // TODO

    var slider = test_slider({
        mode: 'positions',
        values: [0, 25, 50, 75, 100],
        stepped: true
    });

    // POSITIONS (STEPPED)
});

QUnit.test("Pips: Count", function (assert) {

    var slider = test_slider({
        mode: 'count',
        values: 12
    });

    // COUNT

    assert.equal(document.getElementById('qunit-fixture').querySelectorAll('.noUi-value').length, 12, 'Placed requested number of values');

    var pos2 = [];
    Array.prototype.forEach.call(document.getElementById('qunit-fixture').querySelectorAll('.noUi-value'), function (el) {
        pos2.push(parseInt(el.style.left));
    });

    assert.deepEqual(pos2, [0, Math.floor((100 / 11) * 1), Math.floor((100 / 11) * 2), Math.floor((100 / 11) * 3), Math.floor((100 / 11) * 4), Math.floor((100 / 11) * 5), Math.floor((100 / 11) * 6), Math.floor((100 / 11) * 7), Math.floor((100 / 11) * 8), Math.floor((100 / 11) * 9), Math.floor((100 / 11) * 10), 100], 'Values spread evenly');

});

QUnit.test("Pips: Count, values >= 2", function (assert) {

    assert.throws(function () {
        test_slider({
            mode: 'count',
            values: 1
        })
    }, 'Checks minimum number of values');

});

QUnit.test("Pips: Count, stepped", function (assert) {

    assert.expect(0); // TODO

    var slider = test_slider({
        mode: 'count',
        values: 8,
        stepped: true
    });
});

// VALUES

QUnit.test("Pips: Values", function (assert) {

    // #357
    var slider = test_slider({
        mode: 'values',
        values: [50, 552, 750, 940, 5000, 6080, 9000]
    }, 1);

    assert.equal(document.getElementById('qunit-fixture').querySelectorAll('.noUi-value').length, 7, 'Placed requested number of values');
});

QUnit.test("Pips: Values", function(assert) {
    var slider = test_slider({
        mode: 'values',
        values: [10000]
    }, 0);

    assert.equal(document.getElementById('qunit-fixture').querySelectorAll('.noUi-value').length, 1, 'Placed single value at end of range');
});

// VALUES (STEPPED)

QUnit.test("Pips: Values, stepped", function (assert) {

    var slider = test_slider({
        mode: 'values',
        values: [50, 552, 750, 940, 5000, 6080, 9000],
        stepped: true
    });

    assert.equal(document.getElementById('qunit-fixture').querySelectorAll('.noUi-value').length, 6, 'Removed duplicate in step');
});


// #528, #532
QUnit.test("Pips: Values, stepped", function (assert) {

    document.getElementById('qunit-fixture').innerHTML = '<div class="slider"></div>';
    var slider = document.getElementById('qunit-fixture').querySelector('.slider');

    noUiSlider.create(slider, {
        start: -12,
        range: {
            min: -15,
            max: 0.23
        },
        pips: {
            mode: 'positions',
            values: [0, 50, 100]
        }
    });

    var pips = document.getElementById('qunit-fixture').querySelectorAll('.noUi-value');

    assert.ok(pips[pips.length - 1].getAttribute('style').indexOf('left: 100') === 0, 'Last pip is on the right.');
});
