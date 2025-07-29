var slider = document.getElementById('slider');

noUiSlider.create(slider, /* { options } */);

// Set one handled slider
slider.noUiSlider.set(10);
slider.noUiSlider.set([150]);

// Set the upper handle on a slider with two handles,
// don't change the lower one
slider.noUiSlider.set([null, 14]);

// On a slider with three handles,
// set the third to 12 (the handleNumber is 0-indexed),
// fire the set event (default true),
// Don't ignore stepping on the slider (default false)
slider.noUiSlider.setHandle(2, 12, true, true);

// Set both slider handles on a slider with two handles
slider.noUiSlider.set([13.2, 15.7]);

// Set both slider handles on a slider with two handles,
// fire the set event (default true)
// Ignore stepping on the slider (default false)
slider.noUiSlider.set([13.2, 15.7], true, true);

// Return to the 'start' values
// Does NOT reset any other slider properties
slider.noUiSlider.reset();
