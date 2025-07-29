var dragAllSlider = document.getElementById('drag-all');

noUiSlider.create(dragAllSlider, {
    start: [40, 60, 80],
    behaviour: 'drag-all',
    connect: true,
    range: {
        'min': 20,
        'max': 100
    }
});
