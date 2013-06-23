$(document).ready(function() {
	$("#tabs").tabs();
	$(document).tooltip({ track:true });
	$('#tabs .row:even').addClass('highlight');
});
