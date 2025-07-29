/**
 * Example 1
 */
var keyboardSupport = document.getElementById('slider-keyboard')

noUiSlider.create(keyboardSupport, {
    start: 10,
    keyboardSupport: true,      // Default true
    keyboardDefaultStep: 5,     // Default 10
    keyboardPageMultiplier: 10, // Default 5
    range: {
        'min': 0,
        'max': 100
    }
});

/**
 * Example 2
 */
var keyboardSupport2 = document.getElementById('slider-keyboard-2')

noUiSlider.create(keyboardSupport2, {
    start: 100,
    step: 1,
    keyboardSupport: true,      // Default true
    keyboardDefaultStep: 5,     // Default 10
    keyboardPageMultiplier: 100,// Default 5
    keyboardMultiplier: 50,     // Default 1
    range: {
        'min': 0,
        'max': 1000
    }
});
