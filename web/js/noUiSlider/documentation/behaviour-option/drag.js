var dragSlider = document.getElementById('drag');

noUiSlider.create(dragSlider, {
    start: [40, 60],
    behaviour: 'drag',
    connect: true,
    range: {
        'min': 20,
        'max': 80
    }
});
