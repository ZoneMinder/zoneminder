<<<<<<< HEAD
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
			url: '/index.php',
			data: formData,
			dataType: 'html',
			enocde: true
		}).done(function(data) {
			location.reload();
		});


	}



});
=======
function checkState( element )
{
    var form = element.form;

    var minIndex = running?2:1;
    if ( form.runState.selectedIndex < minIndex )
    {
        form.saveBtn.disabled = true;
        form.deleteBtn.disabled = true;
    }
    else
    {
        form.saveBtn.disabled = false;
        form.deleteBtn.disabled = false;
    }

    if ( form.newState.value != '' )
        form.saveBtn.disabled = false;

    // PP if we are in 'default' state, disable delete
    // you can still save
    if (element.value.toLowerCase() == 'default' )
    {
	form.saveBtn.disabled = false;
        form.deleteBtn.disabled = true;
    }
}

function saveState( element )
{
    var form = element.form;

    form.view.value = currentView;
    form.action.value = 'save';
    form.submit();
}

function deleteState( element )
{
    var form = element.form;
    form.view.value = currentView;
    form.action.value = 'delete';
    form.submit();
}

if ( applying )
{
    function submitForm()
    {
        $('contentForm').submit();
    }
    window.addEvent( 'domready', function() { submitForm.delay( 1000 ); } );
}
>>>>>>> master
