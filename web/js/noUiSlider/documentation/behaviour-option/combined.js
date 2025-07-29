var dragTapSlider = document.getElementById('combined');

noUiSlider.create(dragTapSlider, {
    start: [40, 60],
    behaviour: 'drag-smooth-steps-tap',
    step: 10,
    connect: true,
    range: {
        'min': 20,
        'max': 80
    }
});
