var lockedState = false;
var lockedSlider = false;
var lockedValues = [60, 80];

var slider1 = document.getElementById('slider1');
var slider2 = document.getElementById('slider2');

var lockButton = document.getElementById('lockbutton');
var slider1Value = document.getElementById('slider1-span');
var slider2Value = document.getElementById('slider2-span');

// When the button is clicked, the locked state is inverted.
lockButton.addEventListener('click', function () {
    lockedState = !lockedState;
    this.textContent = lockedState ? 'unlock' : 'lock';
});
