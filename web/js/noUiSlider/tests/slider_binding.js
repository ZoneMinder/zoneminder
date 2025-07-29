QUnit.test("Binding", function (assert) {

    assert.expect(15);

    document.getElementById('qunit-fixture').innerHTML = '<div class="slider"></div>';

    var sliders = document.getElementById('qunit-fixture').querySelectorAll('.slider');

    var slider = sliders[0];

    noUiSlider.create(slider, {
        start: [2, 5],
        range: {
            'min': 0,
            'max': 10
        }
    });

    var count = 0;

    // Fires on bind, for every handle (2+2)
    slider.noUiSlider.on('update', function (values, handle) {
        assert.deepEqual(values, ['2.00', '5.00']);
        assert.ok(count++ === handle);
    });

    slider.noUiSlider.off('update');
    count = 0;

    slider.noUiSlider.on('set', function (values, handle) {
        assert.deepEqual(values, ['1.00', '6.00']);
        assert.ok(count++ === handle);
    });

    // Setting a value triggers 'set' for each handle (2+2)
    slider.noUiSlider.set([1, 6]);

    slider.noUiSlider.off('set');

    // Run set again, 'set' shouldn't fire now.
    slider.noUiSlider.set([1, 9]);

    count = 0;

    // Fires once on click (1)
    slider.noUiSlider.on('set.namespace', function (values, handle) {
        assert.ok(true);
    });

    // Fires thrice because of different namespace (1 for mousedown + 2 for set) (3)
    slider.noUiSlider.on('set.othernamespace', function(values, handle) {
        assert.ok(true);
    });

    // Fires once on click (1)
    slider.noUiSlider.on('change.namespace', function (values, handle) {
        assert.ok(true);
    });

    /*
        document.body.addEventListener('mousedown', function (e) {
            console.log(e.clientX, e.clientY);
        });
    */
    function offset(el) {
        var rect = el.getBoundingClientRect();
        return {
            top: rect.top + document.body.scrollTop,
            left: rect.left + document.body.scrollLeft
        };
    }

    function getAriaValues() {
        var domAriaValues = [];
        var handlesArray = Array.prototype.slice.call(slider.querySelectorAll('.noUi-handle'));
        handlesArray.forEach(function(handle) {
            domAriaValues.push(
                Number( handle.getAttribute('aria-valuenow') )
            );
        });
        return domAriaValues;
    }

    var done = assert.async();

    // Do this async, because we can't click the slider before it paints.
    setTimeout(function () {

        simulateMousedown(slider.querySelectorAll('.noUi-origin')[1], offset(slider).left + 100, offset(slider).top + 8);

        slider.noUiSlider.off('.namespace');

        // Should only trigger different namespace
        slider.noUiSlider.set([5, 7]);

        slider.noUiSlider.off('.othernamespace');

        slider.noUiSlider.set([1, 7]);
        // confirm aria value update (1)
        assert.deepEqual(getAriaValues(), [1, 7]);
        // remove all listeners
        slider.noUiSlider.off();
        // modify slider value after removing all listeners
        slider.noUiSlider.set([1, 6]);
        // check if internal listeners are kept as expected  (1)
        assert.deepEqual(getAriaValues(), [1, 6]);

        done();
    }, 300);
});
