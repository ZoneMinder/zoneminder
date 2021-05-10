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
  var tickerText = $('videoProgressTicker').get('text');
  if ( tickerText.length < 1 || tickerText.length > 4 ) {
    $('videoProgressTicker').set('text', '.');
  } else {
    $('videoProgressTicker').appendText('.');
  }
}

function generateVideoResponse( respObj, respText ) {
  window.location.replace(thisUrl+'?view='+currentView+'&eid='+eventId+'&generated='+((respObj.result=='Ok')?1:0));
}

function generateVideo() {
  form = $j('#contentForm')[0];
  var parms = 'view=request&request=event&action=video';
  parms += '&'+$(form).toQueryString();
  var query = new Request.JSON({url: thisUrl, method: 'post', data: parms, onSuccess: generateVideoResponse});
  query.send();
  $('videoProgress').removeClass('hidden');
  $('videoProgress').setProperty('class', 'warnText');
  $('videoProgressText').set('text', videoGenProgressString);
  generateVideoProgress();
  generateVideoTimer = generateVideoProgress.periodical(500);
}
