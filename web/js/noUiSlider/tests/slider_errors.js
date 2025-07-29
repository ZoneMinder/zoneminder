QUnit.test("Errors", function (assert) {

    document.getElementById('qunit-fixture').innerHTML = '<div class="slider"></div>';

    var sliders = document.getElementById('qunit-fixture').querySelectorAll('.slider');
    var slider = sliders[0];

    assert.throws(function () {
        noUiSlider.create(sliders, {
            start: [5],
            range: {
                'min': 0,
                'max': 10
            }
        });
    }, new Error('noUiSlider: create requires a single element, got: [object NodeList]'), 'Gave a nodelist');

    assert.throws(function () {
        noUiSlider.create(slider, null);
    });

    assert.throws(function () {
        noUiSlider.create(slider, undefined);
    });

    assert.throws(function () {
        noUiSlider.create(slider, {
            start: [5],
            range: {
                'min': 0,
                'max': 'nope.'
            }
        });
    });

    assert.throws(function () {
        noUiSlider.create(slider, {
            start: [5],
            step: 'u',
            range: {
                'min': 0,
                'max': 10
            }
        });
    });

    assert.throws(function () {
        noUiSlider.create(slider, {
            start: [5],
            range: {
                'min': 0
            }
        });
    });

    assert.throws(function () {
        noUiSlider.create(slider, {
            start: [5],
            range: 'Over 9000!!!'
        });
    });

    assert.throws(function () {
        noUiSlider.create(slider, {
            start: [],
            range: {
                'min': 0,
                'max': 10
            }
        });
    });

    assert.throws(function () {
        noUiSlider.create(slider, {
            start: [1],
            range: {
                'min': 0,
                'max': 10
            },
            animate: 3
        });
    });

    assert.throws(function () {
        noUiSlider.create(slider, {
            start: 10,
            limit: 5,
            range: {
                'min': 0,
                'max': 10
            }
        });
    }, "Should error if limit enabled with only one handle.");

    assert.throws(function () {
        noUiSlider.create(slider, {
            start: 10,
            padding: [50, 51],
            range: {
                'min': 0,
                'max': 10
            }
        });
    }, "Padding exceeds 100%.");

    assert.throws(function () {
        noUiSlider.create(slider, {
            start: 10,
            margin: 5,
            behaviour: "unconstrained",
            range: {
                'min': 0,
                'max': 10
            }
        });
    }, "Unconstrained can't work with margin.");

    assert.throws(function () {
        noUiSlider.create(slider, {
            start: 0,
            limit: 10,
            step: 20,
            range: {
                'min': 0,
                'max': 100
            }
        });
    }, "Limit must be divisible by step.");

    noUiSlider.create(slider, {
        start: [1, 2, 3],
        margin: 0, // Does not throw, issue #582
        cssPrefix: null, // #856
        step: null,
        range: {
            'min': 0,
            'max': 10
        }
    });

    assert.throws(function () {
        slider.noUiSlider.setHandle(-1, 5);
    }, "Out of bounds handle number");

    assert.throws(function () {
        slider.noUiSlider.setHandle(4, 5);
    }, "Out of bounds handle number");

    assert.throws(function () {
        noUiSlider.create(slider, {
            start: 10,
            range: {
                'min': 0,
                'max': 10
            }
        });
    }, "Should error if slider already initialised");
});
