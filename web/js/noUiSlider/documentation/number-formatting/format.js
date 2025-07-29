noUiSlider.create(slider, {
    /* ... */
    format: {
        // 'to' the formatted value. Receives a number.
        to: function (value) {
            return value + ',-';
        },
        // 'from' the formatted value.
        // Receives a string, should return a number.
        from: function (value) {
            return Number(value.replace(',-', ''));
        }
    }
});
