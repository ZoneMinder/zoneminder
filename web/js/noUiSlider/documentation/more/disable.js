var slider1 = document.getElementById('disable1'),
    slider2 = document.getElementById('disable2'),
    checkbox1 = document.getElementById('checkbox1'),
    checkbox2 = document.getElementById('checkbox2'),
    checkbox3 = document.getElementById('checkbox3');

function toggle(checkbox, slider, handleNumber) {

    // If the checkbox is checked, disabled the slider.
    // Otherwise, re-enable it.
    if (checkbox.checked) {
        slider.noUiSlider.disable(handleNumber);
    } else {
        slider.noUiSlider.enable(handleNumber);
    }
}

noUiSlider.create(slider1, {
    start: 80,
    connect: [true, false],
    range: {
        min: 0,
        max: 100
    }
});

noUiSlider.create(slider2, {
    start: [20, 80],
    range: {
        min: 0,
        max: 100
    }
});

checkbox1.addEventListener('click', function () {
    toggle(this, slider1);
});

checkbox2.addEventListener('click', function () {
    toggle(this, slider2, 0);
});

checkbox3.addEventListener('click', function () {
    toggle(this, slider2, 1);
});
