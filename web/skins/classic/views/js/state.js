$j(document).ready(function() {
	// Enable or disable the Delete button depending on the selected run state
	$j("#runState").change(function() {
		runstate = $j(this).val();

		if ( (runstate == 'stop') || (runstate == 'restart') || (runstate == 'start') || (runstate == 'default') ) {
			$j("#btnDelete").prop( "disabled", true );
		} else {
			$j("#btnDelete").prop( "disabled", false );
		}
	});

	// Enable or disable the Save button when entering a new state
	$j("#newState").keyup(function() {
		length = $j(this).val().length;
		console.log(length);
		if (length < 1) {
			$j("#btnSave").prop( "disabled", true );
		} else {
			$j("#btnSave").prop( "disabled", false );
		}
	});
	

	// Delete a state
	$j("#btnDelete").click(function() {
    		StateStuff( 'delete', $j("#runState").val( ));
	});


	// Save a new state
	$j("#btnSave").click(function() {
		StateStuff( 'save', undefined, $j("#newState").val() );
	});

	// Change state
	$j("#btnApply").click(function() {
		StateStuff( 'state', $j("#runState").val() );
	});

	function StateStuff( action, runState, newState ){
		var formData = {
			'view' : 'console',
			'action' : action,
			'apply' : 1,
			'runState' : runState,
			'newState' : newState
		};
		console.log(formData);

		$j("#pleasewait").toggleClass("hidden");

		$j.ajax({
			type: 'POST',
			url: thisUrl,
			data: formData,
			dataType: 'html',
			enocde: true
		}).done(function(data) {
			location.reload();
		});

	}

});
