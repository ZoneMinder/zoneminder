QUnit.test("Standard range", function (assert) {

    var subject = new noUiSlider.default.__spectrum({
        min: 0,
        max: 100
    }, false);

    assert.equal(subject.convert(0), 0);
    assert.equal(subject.convert(10), 10);
    assert.equal(subject.convert(50.2), 50.2);
    assert.equal(subject.convert(100), 100);
});

QUnit.test("Standard step", function (assert) {

    var subject = new noUiSlider.default.__spectrum({
        min: 0,
        max: 100
    }, false, 15);

    assert.equal(subject.convert(0), 0);
    assert.equal(subject.convert(10), 15);
    assert.equal(subject.convert(50.2), 45);
    assert.equal(subject.convert(95), 90);
    assert.equal(subject.convert(100), 100);
});
