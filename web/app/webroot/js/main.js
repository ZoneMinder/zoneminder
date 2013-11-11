$(document).ready(function() {
	
  // Date/Time Pickers //
  $('.datetime').datetimepicker({
    pick12HourFormat: true,
    pickSeconds: false
  });
  // Date/Time Pickers //
  
  // Fullscreen //
  var fullscreen = false;
  $('#toggle-fullscreen').click(function() {
    if (fullscreen) {
      $('#header, #sidebar, #footer').show();
      fullscreen = false;
      $('#main-content').removeClass('fullscreen');
    } else {
      $('#header, #sidebar, #footer').hide();
      fullscreen = true;
      $('#main-content').addClass('fullscreen');
    }
  });
  // Fullscreen //

  // Version Polling //
  setTimeout(function() {
     $.post('/Version/isUpdateAvailable', function(data) {
      if (data === 'true') {
        $('#version').append(' - An update is available!');
        $('#version span.label').removeClass('label-success').addClass('label-danger');
      }
     });
   }, 300000);
  // Version Polling //

	// Logs //
	$("#LogsComponent").change(function(){
		if (!!$(this).val()) {
			$("#tblComponents").load("/logs/index/Component:" + $(this).val() + ' #tblComponents');
		} else {
			$("#tblComponents").load('/logs/index/ #tblComponents');
		}
	});

	$("#btnComponentRefresh").button().click(function(){
		if (!!$("#Component").val()) {
			$("#tblComponents").load("/logs/index/Component:" + $("#Component").val() + ' #tblComponents');
		} else {
			$("#tblComponents").load('/logs/index/ #tblComponents');
		}
	});
    // Expand/collapse messages
    $('.log-message').expander({
      slicePoint: 90,
      expandPrefix: '..',
      expandText: '(more)',
      userCollapseText: '(less)',
      preserveWords: true,
      expandEffect: 'show',
      expandSpeed: 0,
      collapseEffect: 'hide',
      collapseSpeed: 0
    });
    // Expand/collapse messages
	// Logs //
	
	// Events //
	$("#Events a").colorbox({
		rel: 'events',
		preloading: true,

	});

	$("#Events_list").selectable({ appendTo: "#Events_list li", filter: "li" });

	$("#EventsButtonDelete").click(function () {
		$("#Events_list li.ui-selected").each(function() {
			console.log($(this).attr('id'));
		});
	});

	$("#EventsButtonSearch").button();
	$('#EventsIndexForm').submit(function() {
    $('#main-content-body').load('/events/index #EventsContent', $('#EventsIndexForm').serialize());
  });

  $( "#EventStartDate" ).datepicker({
		changeMonth: true,
		changeYear: true,
		defaultDate: -1,
		onClose: function( selectedDate ) {
			$("#EventEndDate").datepicker( "option", "minDate", selectedDate );
		}
	});
	$("#EventStartDate").datepicker( "option", "dateFormat", "MM d, yy" );

	$( "#EventEndDate" ).datepicker({
		changeMonth: true,
		changeYear: true,
		defaultDate: +0,
		onClose: function( selectedDate ) {
			$("#EventStartDate").datepicker( "option", "maxDate", selectedDate );
		}
	});
	$("#EventEndDate").datepicker( "option", "dateFormat", "MM d, yy" );

	$("#PreviousEvent").button({
		text: false,
		icons: { primary: 'ui-icon-seek-start' }
	});
	$("#NextEvent").button({
		text: false,
		icons: { primary: 'ui-icon-seek-end' }
	});
	$("#RewindEvent").button({
		text: false,
		icons: { primary: 'ui-icon-seek-prev' }
	});
	$("#FastForwardEvent").button({
		text: false,
		icons: { primary: 'ui-icon-seek-next' }
	});

	$("#PlayEvent").button({
		 text: false,
		 icons: { primary: "ui-icon-play" }
	 })
	 .click(function() {
		 var options;
		 if ( $( this ).text() === "play" ) {
			 options = {
				 label: "pause",
		 icons: { primary: "ui-icon-pause" }
			 };
			 console.log('Pausing!');
		 } else {
			 options = {
				 label: "play",
		 icons: { primary: "ui-icon-play" }
			 };
		 }
		 $( this ).button( "option", options );
	 });

	$("#ZoomOutEvent").button({
		text: false,
		icons: { primary: 'ui-icon-zoomout' }
	});



	$("#daemonStatus").button();
	$("#daemonStatus").click(function(){
		$.post("/app/daemonControl", {command:'stop'})
		.done(function(data) {
			console.log(data);
		});
	});

	$(".EventMonitor").click(function() {
		$(this).toggleClass("active");
	});

	
    
    // Select All Events //
    $('body').on('click', 'input[type=checkbox].selectAll', function() {
      $(this).closest('table').find(':checkbox').prop('checked', this.checked);
    });
    // Select All Events //
    
	// Events //

	// Config //
	$('.nav-tabs a').click(function (e) {
	  e.preventDefault()
	  $(this).tab('show')
	})

	$("div#config.tab-pane").addClass('active');

	$(document).tooltip({ track:true });
	$('#tabs .row:even').addClass('highlight');
	// Config //
	
	// Global //
	$('#loadingDiv').hide();
	$(document)
		.ajaxStart(function() {
			$('#loadingDiv').show();
		})
		.ajaxStop(function() {
			$('#loadingDiv').hide();
		});
	// Global //
	
	// Monitors //
	 $(".functions").buttonset();
	 $(".status").button()
		 .click(function() {
			 if ( $(this).text() == 'Enabled' ) {
				 $status = 0;
			 } else {
				 $status = 1;
			 }
			 $mid = $(this).attr('id').split("_")[0];
			 $.post("/Monitors/index/", { status: $status, mid: $mid }, function(data) {
				 console.log(data);
			 });
		 });

	 $("#ConfigIndexForm").submit(function() {
		 var formData = $(this).serialize();
		 var formUrl = $(this).attr('action');

		 $.ajax({
			 type: 'POST',
			 url: formUrl,
			 data: formData,
			 success: function(data){
				 console.log(data);
			 },
			 error: function(xhr, textStatus, error){
				 console.log(textStatus);
			 }
		 });
	 });
	 
	// Monitors //

});
