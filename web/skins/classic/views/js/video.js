function deleteVideo(e) {
  index = e.getAttribute('data-file-index');
  window.location.replace(thisUrl+'?view='+currentView+'&eid='+eventId+'&deleteIndex='+index);
}

function downloadVideo(e) {
  index = e.getAttribute('data-file-index');
  window.location.replace(thisUrl+'?view='+currentView+'&eid='+eventId+'&downloadIndex='+index);
}

var generateVideoTimer = null;

function generateVideoProgress() {
  var tickerText = $j('#videoProgressTicker').text();
  if ( tickerText.length < 1 || tickerText.length > 4 ) {
    $j('#videoProgressTicker').text('.');
  } else {
    $j('videoProgressTicker').append('.');
  }
}

function generateVideoResponse( respObj, respText ) {
  window.location.replace(thisUrl+'?view='+currentView+'&eid='+eventId+'&generated='+((respObj.result=='Ok')?1:0));
}

function generateVideo() {
  var form = $j('#videoForm').serialize();
  $j.getJSON(thisUrl + '?view=request&request=event&action=video', form)
      .done(generateVideoResponse)
      .fail(logAjaxFail);
  $j('#videoProgress').removeClass('hidden');
  $j('#videoProgress').addClass('warnText');
  $j('#videoProgressText').text(videoGenProgressString);
  generateVideoProgress();
  generateVideoTimer = generateVideoProgress.periodical(500);
}
