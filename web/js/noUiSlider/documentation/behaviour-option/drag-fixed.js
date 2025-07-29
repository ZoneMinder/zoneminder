var dragFixedSlider = document.getElementById('drag-fixed');

noUiSlider.create(dragFixedSlider, {
    start: [40, 60],
    behaviour: 'drag-fixed',
    connect: true,
    range: {
        'min': 20,
        'max': 80
    }
});
