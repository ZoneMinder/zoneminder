QUnit.test("Slider with three or more handles", function (assert) {

    document.getElementById('qunit-fixture').innerHTML = '\
			<div id="slider1"></div>\
			<div id="slider2" style="position: fixed; left: 10px; top: 10px;" ></div>\
		';

    var slider = document.getElementById('slider1');

    noUiSlider.create(slider, {
        start: [5, 10, 15],
        range: {
            'min': [0, 1],
            'max': [20]
        }
    });

    assert.deepEqual(slider.noUiSlider.get(true), [5, 10, 15]);

    slider.noUiSlider.set([0, 0, 1]);
    assert.deepEqual(slider.noUiSlider.get(true), [0, 0, 1]);

    slider.noUiSlider.set([19, 20, 20]);
    assert.deepEqual(slider.noUiSlider.get(true), [19, 20, 20]);


    // xnakos spotted a bug where handles can stack wrong, so if there are
    // two handles in the same place, one movable and one not, sometimes
    // when you try to drag you get the non-movable one so they get stuck.
    // See https://github.com/michaeltandy/noUiSlider/pull/3

    var slider2 = document.getElementById('slider2');
    noUiSlider.create(slider2, {
        start: [10, 20, 20],
        range: {
            'min': [0, 1],
            'max': [20]
        },
        animate: false,
        animationDuration: 0
    });

    var handles2 = slider2.querySelectorAll('.noUi-handle');
    var leftmostHandle = handles2[0];
    var middleHandle = handles2[1];
    var rightmostHandle = handles2[2];
    var leftmostHandlePos = leftmostHandle.getBoundingClientRect();
    var middleHandlePos = middleHandle.getBoundingClientRect();
    var rightmostHandlePos = rightmostHandle.getBoundingClientRect();

    assert.deepEqual(middleHandlePos, rightmostHandlePos, "Two handles in the same location should have the same on-screen position");
    assert.notDeepEqual(middleHandlePos, leftmostHandlePos, "Handles at different ends of the slider should have different positions.");

    var middleHandleX = (middleHandlePos.right + middleHandlePos.left) / 2;
    var middleHandleY = (middleHandlePos.top + middleHandlePos.bottom) / 2;
    var selectedByClick = document.elementFromPoint(middleHandleX, middleHandleY);
    assert.strictEqual(selectedByClick, middleHandle.querySelector('.noUi-touch-area'), "Middle handle should be selected by click as rightmost handle is unmovable move");


    // xnakos also spotted a bug where clicking
    // (or tapping, if you're on a tablet)
    // wouldn't always move the handle you'd expect it to move.
    // See https://github.com/michaeltandy/noUiSlider/pull/2
    var clickTarget = slider2.querySelector('div.noUi-base');
    var clickY = middleHandleY;
    var click1x = leftmostHandlePos.right * 0.75 + middleHandlePos.left * 0.25;
    var click2x = leftmostHandlePos.right * 0.25 + middleHandlePos.left * 0.75;

    assert.deepEqual(slider2.noUiSlider.get(), ["10.00", "20.00", "20.00"], "Checking initial state");

    simulateMousedown(clickTarget, click1x, clickY);
    assert.deepEqual(slider2.noUiSlider.get(), ["13.00", "20.00", "20.00"], "Expect click nearer left handle to move left handle");

    slider2.noUiSlider.set([10, 20, 20]);
    assert.deepEqual(slider2.noUiSlider.get(), ["10.00", "20.00", "20.00"], "Checking reset between clicks");

    simulateMousedown(clickTarget, click2x, clickY);
    assert.deepEqual(slider2.noUiSlider.get(), ["10.00", "17.00", "20.00"], "Expect click nearer middle & right handles to move middle handle");

    slider2.noUiSlider.set([10, 10, 10]);

    simulateMousedown(clickTarget, click2x, clickY);
    assert.deepEqual(slider2.noUiSlider.get(), ["10.00", "10.00", "17.00"], "Expect click after last handle with all in the same position to move the last handle");

});
