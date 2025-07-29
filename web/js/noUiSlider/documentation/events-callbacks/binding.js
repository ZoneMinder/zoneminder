function doSomething(values, handle, unencoded, tap, positions, noUiSlider) {
    // values: Current slider values (array);
    // handle: Handle that caused the event (number);
    // unencoded: Slider values without formatting (array);
    // tap: Event was caused by the user tapping the slider (boolean);
    // positions: Left offset of the handles (array);
    // noUiSlider: slider public Api (noUiSlider);
}

// Binding signature
slider.noUiSlider.on(eventName, doSomething);

// Binding namespaced events
slider.noUiSlider.on('set.one', function () { });
slider.noUiSlider.on('change.one', function () { });

// Remove all events in the 'one' namespace.
slider.noUiSlider.off('.one');

// Remove all events
slider.noUiSlider.off();

// Remove all 'change' events in any namespace.
slider.noUiSlider.off('change');
