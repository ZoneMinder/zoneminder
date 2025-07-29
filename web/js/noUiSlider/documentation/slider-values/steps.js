// Example slider:
noUiSlider.create(slider, {
	start: [20, 80],
	range: {
		'min': [0],
		'10%': [10, 10],
		'50%': [80, 50],
		'80%': 150,
		'max': 200
	}
});

// Both handles step 10 up and down
.set([30, 50])
.steps() => [[10,10],[10,10]]

// Second handle now steps up 50
.set([30, 80])
.steps() => [[10,10],[10,50]]

// Second handle now steps down 50, steps up 20 to
// reach 150 at 80% of the slider
.set([30, 130])
.steps() => [[10,10],[50,20]]

// Second handle steps down 20 to go back to 130.
// the is no step value to go up
.set([30, 150])
.steps() => [[10,10],[20,false]]

// Second handle has no downward step,
// is at the end of the slider
.set([30, 200])
.steps() => [[10,10],[false,null]]
