QUnit.test("Padding and margin (lookaround)", function (assert) {

    document.getElementById('qunit-fixture').innerHTML = '<div id="slider1"></div><div id="slider2"></div><div id="slider3"></div>';

    var slider = document.getElementById('qunit-fixture').querySelector('#slider1');
    var slider2 = document.getElementById('qunit-fixture').querySelector('#slider2');
    var slider3 = document.getElementById('qunit-fixture').querySelector('#slider3');

    noUiSlider.create(slider, {
        start: [20, 23],
        step: 1,
        padding: 5,
        margin: 3,
        range: {
            'min': 10,
            'max': 30
        }
    });

    function m(a) {
        var values = slider.noUiSlider.get();
        values[0] = parseInt(values[0]) + a;
        values[1] = parseInt(values[1]) + a;
        slider.noUiSlider.set(values);
    }

    function up() {
        m(1);
    }

    function down() {
        m(-1);
    }

    assert.deepEqual(slider.noUiSlider.get(), ['20.00', '23.00']);

    up();
    assert.deepEqual(slider.noUiSlider.get(), ['21.00', '24.00']);

    up();
    assert.deepEqual(slider.noUiSlider.get(), ['22.00', '25.00'], 'Highest possible values');

    up();
    assert.deepEqual(slider.noUiSlider.get(), ['22.00', '25.00'], 'Nothing should have happened');

    down();
    assert.deepEqual(slider.noUiSlider.get(), ['21.00', '24.00']);

    down();
    assert.deepEqual(slider.noUiSlider.get(), ['20.00', '23.00']);

    slider.noUiSlider.set([10, 12]);
    assert.deepEqual(slider.noUiSlider.get(), ['15.00', '18.00'], 'lower bound by padding, upper by margin');

    slider.noUiSlider.set([15, 17]);
    assert.deepEqual(slider.noUiSlider.get(), ['15.00', '18.00'], 'lower bound ok, but apply margin');


    noUiSlider.create(slider2, {
        start: [20, 23, 27],
        step: 1,
        padding: 3,
        limit: 4,
        margin: 3,
        range: {
            'min': 10,
            'max': 40
        }
    });

    slider2.noUiSlider.set([20, 24, 27]);
    assert.deepEqual(slider2.noUiSlider.get(), ['20.00', '24.00', '27.00']);

    slider2.noUiSlider.set([19, 24, 27]);
    assert.deepEqual(slider2.noUiSlider.get(), ['19.00', '23.00', '27.00'], 'Cannot do 24, exceeds limit');

    slider2.noUiSlider.set([18, 19, 23]);
    assert.deepEqual(slider2.noUiSlider.get(), ['18.00', '21.00', '24.00'], 'Cannot do 19, exceeds margin. Same for 23 after 19 becomes 21.');

    slider2.noUiSlider.set([18, 19, 29]);
    assert.deepEqual(slider2.noUiSlider.get(), ['18.00', '21.00', '25.00'], 'Cannot do 25, exceeds limit.');

    slider2.noUiSlider.set([12, 15, 40]);
    assert.deepEqual(slider2.noUiSlider.get(), ['13.00', '16.00', '20.00'], 'Padding, margin and limit');


    noUiSlider.create(slider3, {
        start: [20, 23, 27],
        step: 3,
        padding: 6,
        limit: 9,
        behaviour: 'drag',
        margin: 6,
        range: {
            'min': 0,
            'max': 100
        }
    });

    slider3.noUiSlider.set([20, 24, 27]);
    assert.deepEqual(slider3.noUiSlider.get(), ['21.00', '27.00', '33.00'], 'Margin and step');

    slider3.noUiSlider.set([21, 27, 36]);
    assert.deepEqual(slider3.noUiSlider.get(), ['21.00', '27.00', '36.00'], 'Limit');

    slider3.noUiSlider.__moveHandles(true, 10, [0, 1]); // Drag the first two handles up by 10 pct
    assert.deepEqual(slider3.noUiSlider.get(), ['24.00', '30.00', '36.00'], 'Margin and limit for two handles');

    slider3.noUiSlider.__moveHandles(true, 20, [0, 1]); // Try to push up further
    assert.deepEqual(slider3.noUiSlider.get(), ['24.00', '30.00', '36.00'], 'Nothing should change');

    slider3.noUiSlider.__moveHandles(true, 10, [1, 2]); // Move the last two handles
    assert.deepEqual(slider3.noUiSlider.get(), ['24.00', '33.00', '39.00'], 'Can slide once');

    slider3.noUiSlider.__moveHandles(true, 10, [1, 2]); // Move the last two handles
    assert.deepEqual(slider3.noUiSlider.get(), ['24.00', '33.00', '39.00'], 'Not again, blocked by limit on the 2nd handle.');

    slider3.noUiSlider.set([76, 85, 91]);
    assert.deepEqual(slider3.noUiSlider.get(), ['75.00', '84.00', '90.00']);

    slider3.noUiSlider.set([78, 85, 91]);
    assert.deepEqual(slider3.noUiSlider.get(), ['78.00', '84.00', '90.00']);

    slider3.noUiSlider.__moveHandles(true, 10, [1, 2]); // Move the last two handles
    assert.deepEqual(slider3.noUiSlider.get(), ['78.00', '87.00', '93.00']);

    slider3.noUiSlider.__moveHandles(false, -10, [1, 2]); // Back down
    assert.deepEqual(slider3.noUiSlider.get(), ['78.00', '84.00', '90.00']);

    slider3.noUiSlider.__moveHandles(false, -10, [0, 1]); // Back down
    assert.deepEqual(slider3.noUiSlider.get(), ['75.00', '81.00', '90.00']);

    slider3.noUiSlider.__moveHandles(false, -10, [0, 1]); // Back down
    assert.deepEqual(slider3.noUiSlider.get(), ['75.00', '81.00', '90.00'], 'Cannot, limited by limit between last handles');


});

QUnit.test("Padding and margin (lookaround) (non-linear)", function (assert) {

    document.getElementById('qunit-fixture').innerHTML = '<div id="slider1"></div><div id="slider2"></div><div id="slider3"></div>';

    var slider = document.getElementById('qunit-fixture').querySelector('#slider1');
    var slider2 = document.getElementById('qunit-fixture').querySelector('#slider2');
    var slider3 = document.getElementById('qunit-fixture').querySelector('#slider3');

    noUiSlider.create(slider, {
        start: [20, 23],
        step: 1,
        padding: 5,
        margin: 3,
        range: {
            'min': 10,
            '50%': 15,
            'max': 30
        }
    });

    function m(a) {
        var values = slider.noUiSlider.get();
        values[0] = parseInt(values[0]) + a;
        values[1] = parseInt(values[1]) + a;
        slider.noUiSlider.set(values);
    }

    function up() {
        m(1);
    }

    function down() {
        m(-1);
    }

    assert.deepEqual(slider.noUiSlider.get(), ['20.00', '23.00']);

    up();
    assert.deepEqual(slider.noUiSlider.get(), ['21.00', '24.00']);

    up();
    assert.deepEqual(slider.noUiSlider.get(), ['22.00', '25.00'], 'Highest possible values');

    up();
    assert.deepEqual(slider.noUiSlider.get(), ['22.00', '25.00'], 'Nothing should have happened');

    down();
    assert.deepEqual(slider.noUiSlider.get(), ['21.00', '24.00']);

    down();
    assert.deepEqual(slider.noUiSlider.get(), ['20.00', '23.00']);

    slider.noUiSlider.set([10, 12]);
    assert.deepEqual(slider.noUiSlider.get(), ['15.00', '18.00'], 'lower bound by padding, upper by margin');

    slider.noUiSlider.set([15, 17]);
    assert.deepEqual(slider.noUiSlider.get(), ['15.00', '18.00'], 'lower bound ok, but apply margin');


    noUiSlider.create(slider2, {
        start: [20, 23, 27],
        step: 1,
        padding: 3,
        limit: 4,
        margin: 3,
        range: {
            'min': 10,
            '25%': 20,
            'max': 40
        }
    });

    slider2.noUiSlider.set([20, 24, 27]);
    assert.deepEqual(slider2.noUiSlider.get(), ['20.00', '24.00', '27.00']);

    slider2.noUiSlider.set([19, 24, 27]);
    assert.deepEqual(slider2.noUiSlider.get(), ['19.00', '23.00', '27.00'], 'Cannot do 24, exceeds limit');

    slider2.noUiSlider.set([18, 19, 23]);
    assert.deepEqual(slider2.noUiSlider.get(), ['18.00', '21.00', '24.00'], 'Cannot do 19, exceeds margin. Same for 23 after 19 becomes 21.');

    slider2.noUiSlider.set([18, 19, 29]);
    assert.deepEqual(slider2.noUiSlider.get(), ['18.00', '21.00', '25.00'], 'Cannot do 25, exceeds limit.');

    slider2.noUiSlider.set([12, 15, 40]);
    assert.deepEqual(slider2.noUiSlider.get(), ['13.00', '16.00', '20.00'], 'Padding, margin and limit');


    noUiSlider.create(slider3, {
        start: [20, 23, 27],
        padding: 6,
        limit: 12,
        behaviour: 'drag',
        margin: 6,
        range: {
            'min': [0, 3],
            '50%': [50, 6],
            'max': [100, 6]
        }
    });

    slider3.noUiSlider.set([20, 24, 27]);
    assert.deepEqual(slider3.noUiSlider.get(), ['21.00', '27.00', '33.00'], 'Margin and step');

    slider3.noUiSlider.set([21, 27, 36]);
    assert.deepEqual(slider3.noUiSlider.get(), ['21.00', '27.00', '36.00'], 'Limit');

    slider3.noUiSlider.__moveHandles(true, 10, [0, 1]); // Drag the first two handles up by 10 pct
    assert.deepEqual(slider3.noUiSlider.get(), ['24.00', '30.00', '36.00'], 'Margin and limit for two handles');

    slider3.noUiSlider.__moveHandles(true, 20, [0, 1]); // Try to push up further
    assert.deepEqual(slider3.noUiSlider.get(), ['24.00', '30.00', '36.00'], 'Nothing should change');

    slider3.noUiSlider.__moveHandles(true, 10, [1, 2]); // Move the last two handles
    assert.deepEqual(slider3.noUiSlider.get(), ['24.00', '36.00', '42.00'], 'Can slide once');

    slider3.noUiSlider.__moveHandles(true, 10, [1, 2]); // Move the last two handles
    assert.deepEqual(slider3.noUiSlider.get(), ['24.00', '36.00', '42.00'], 'Not again, blocked by limit on the 2nd handle.');

    slider3.noUiSlider.set([11, 24, 36]);
    assert.deepEqual(slider3.noUiSlider.get(), ['12.00', '24.00', '36.00']);

    slider3.noUiSlider.set([76, 85, 91]);
    assert.deepEqual(slider3.noUiSlider.get(), ['74.00', '86.00', '92.00']);

    slider3.noUiSlider.__moveHandles(true, 10, [1, 2]); // Move the last two handles
    assert.deepEqual(slider3.noUiSlider.get(), ['74.00', '86.00', '92.00']);

    slider3.noUiSlider.__moveHandles(false, -10, [1, 2]); // Back down
    assert.deepEqual(slider3.noUiSlider.get(), ['74.00', '80.00', '86.00']);

    slider3.noUiSlider.__moveHandles(false, -10, [0, 1]); // Back down
    assert.deepEqual(slider3.noUiSlider.get(), ['68.00', '74.00', '86.00']);

    slider3.noUiSlider.__moveHandles(false, -10, [0, 1]); // Back down
    assert.deepEqual(slider3.noUiSlider.get(), ['68.00', '74.00', '86.00'], 'Cannot, limited by limit between last handles');


});
