QUnit.test("Testing ltr and rtl non-linear", function (assert) {

    document.getElementById('qunit-fixture').innerHTML = '\
			<div class="sliderRTL"></div>\
			<div class="sliderLTR"></div>\
		';

    var sliderRTL = document.getElementById('qunit-fixture').querySelector('.sliderRTL');
    var sliderLTR = document.getElementById('qunit-fixture').querySelector('.sliderLTR');

    noUiSlider.create(sliderRTL, {
        range: {
            'min': 0,
            '30%': 5,
            '50%': [10, 10],
            'max': 50
        },
        start: 44,
        direction: 'rtl',
        format: {
            to: function (x) {
                return Math.round(x).toString();
            }, from: Number
        }
    });

    noUiSlider.create(sliderLTR, {
        range: {
            'min': 0,
            '30%': 5,
            '50%': [10, 10],
            'max': 50
        },
        start: 44,
        format: {
            to: function (x) {
                return Math.round(x).toString();
            }, from: Number
        }
    });

    assert.equal(sliderRTL.noUiSlider.get(), '40', 'Start stepping on rtl works');
    assert.equal(sliderLTR.noUiSlider.get(), '40', 'Start stepping on ltr works');

    sliderRTL.noUiSlider.set(42);
    sliderLTR.noUiSlider.set(42);

    assert.equal(sliderRTL.noUiSlider.get(), '40', 'RTL slider stepped by 10 in upper half.');
    assert.equal(sliderLTR.noUiSlider.get(), '40', 'LTR slider stepped by 10 in upper half.');

    sliderRTL.noUiSlider.set(6);
    sliderLTR.noUiSlider.set(6);

    assert.equal(sliderRTL.noUiSlider.get(), '6', 'RTL slider didn\'t step in lower half.');
    assert.equal(sliderLTR.noUiSlider.get(), '6', 'LTR slider didn\'t step in lower half.');
});
