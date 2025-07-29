// Test file to check Typescript types for the .js dist
import noUiSlider from 'dist/nouislider.js';

const element: HTMLElement|null = document.querySelector('#slider');

if (element) {

    noUiSlider.create(element, {
        start: [20, 50],
        range: {
            min: 0,
            '50%': 30,
            max: 100
        }
    });

    const range = {
        min: 0,
        '50%': 30,
        max: 100
    };

    noUiSlider.create(element, {
        start: [20, 50],
        range: range
    });
}
