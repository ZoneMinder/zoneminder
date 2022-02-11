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
  $j.ajaxSetup({
    timeout: 0
  });
  var form = $j('#videoForm').serialize();
  $j.getJSON(thisUrl + '?view=request&request=event&action=video', form)
      .done(generateVideoResponse)
      .fail(logAjaxFail);
  $j('#videoProgress').removeClass('invisible');
}

function initPage() {
  var backBtn = $j('#backBtn');
  var videoBtn = $j('#videoBtn');

  videoBtn.prop('disabled', !opt_ffmpeg);

  // Manage the BACK button
  document.getElementById("backBtn").addEventListener("click", function onBackClick(evt) {
    evt.preventDefault();
    window.history.back();
  });

  // Manage the REFRESH Button
  document.getElementById("refreshBtn").addEventListener("click", function onRefreshClick(evt) {
    evt.preventDefault();
    window.location.reload(true);
  });

  // Don't enable the back button if there is no previous zm page to go back to
  backBtn.prop('disabled', !document.referrer.length);
}

$j(document).ready(initPage);
