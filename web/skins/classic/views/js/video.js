function deleteVideo(e) {
  index = e.getAttribute('data-file-index');
  window.location.replace(thisUrl+'?view='+currentView+'&eid='+eventId+'&deleteIndex='+index);
}

function downloadVideo(e) {
  index = e.getAttribute('data-file-index');
  window.location.replace(thisUrl+'?view='+currentView+'&eid='+eventId+'&downloadIndex='+index);
}

function generateVideoResponse( data, responseText ) {
  console.log(data);

  var generated = (data.result=='Ok') ? 1 : 0;
  var fullUrl = thisUrl + '?view=' + currentView + '&eid=' + eventId + '&generated=' + generated;
  
  $j('#videoProgress').removeClass( 'text-warning' );
  if ( generated ) {
    $j('#videoProgress').addClass( 'text-success' );
    $j('#videoProgress').text(exportSucceededString);
    $j( "#videoTable" ).load( fullUrl+ ' #videoTable' );
  } else {
    $j('#videoProgress').addClass( 'text-danger' );
    $j('#videoProgress').text(exportFailedString);
  }
}

function generateVideo() {
  var form = $j('#videoForm').serialize();
  $j.getJSON(thisUrl + '?view=request&request=event&action=video', form)
      .done(generateVideoResponse)
      .fail(logAjaxFail);
  $j('#videoProgress').removeClass('invisible');
}
