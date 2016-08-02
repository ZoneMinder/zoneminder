$(document).ready(function() {
	// Enable or disable the Delete button depending on the selected run state
	$("#runState").change(function() {
		runstate = $(this).val();

		if ( (runstate == 'stop') || (runstate == 'restart') || (runstate == 'start') || (runstate == 'default') ) {
			$("#btnDelete").prop( "disabled", true );
		} else {
			$("#btnDelete").prop( "disabled", false );
		}
	});

	// Enable or disable the Save button when entering a new state
	$("#newState").keyup(function() {
		length = $(this).val().length;
		console.log(length);
		if (length < 1) {
			$("#btnSave").prop( "disabled", true );
		} else {
			$("#btnSave").prop( "disabled", false );
		}
	});
	

	// Delete a state
	$("#btnDelete").click(function() {
    		StateStuff( 'delete', $("#runState").val( ));
	});


	// Save a new state
	$("#btnSave").click(function() {
		StateStuff( 'save', undefined, $("#newState").val() );
		
	});

	// Change state
	$("#btnApply").click(function() {
		StateStuff( 'state', $("#runState").val() );
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

		$("#pleasewait").toggleClass("hidden");

		$.ajax({
			type: 'POST',
			url: '/index.php',
			data: formData,
			dataType: 'html',
			enocde: true
		}).done(function(data) {
			location.reload();
		});


	}



});
