function generateVideoResponse( data, responseText ) {
  console.log(data);

  var generated = (data.result=='Ok') ? 1 : 0;
  var fullUrl = thisUrl + '?view=' + currentView + '&eid=' + eventId + '&generated=' + generated;

  $j('#videoProgress').removeClass( 'text-warning' );
  if ( generated ) {
    $j('#videoProgress').addClass( 'text-success' );
    $j('#videoProgress').text(videoGenSuccessString);
    $j( "#videoTable" ).load( fullUrl+ ' #videoTable' );
  } else {
    $j('#videoProgress').addClass( 'text-danger' );
    $j('#videoProgress').text(videoGenFailedString);
  }
}

function generateVideo() {
  $j.ajaxSetup({
    timeout: 0
  });
  var form = $j('#videoForm').serialize();
  $j.getJSON(thisUrl + '?view=request&request=event&action=video', form)
      .done(generateVideoResponse)
      .fail(function(jqxhr, textStatus, error) {
        generateVideoResponse({result: 'Error'});
        logAjaxFail(jqxhr, textStatus, error);
      });
  // A previous result replaced the spinner and message, so put them back.
  $j('#videoProgress').removeClass('invisible text-success text-danger').addClass('text-warning')
      .html('<span class="spinner-grow" role="status" aria-hidden="true"></span> ')
      .append(document.createTextNode(videoGenProgressString));
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

  videoobj = $j('#videoobj');
  if (videoobj.length) {
    vid = videojs('videoobj');
  }
}

$j(document).ready(initPage);
