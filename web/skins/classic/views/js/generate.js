
var generateVideoTimer = null;

function generateVideoProgress() {
  var tickerText = $('videoProgressTicker').get('text');
  if ( tickerText.length < 1 || tickerText.length > 4 )
    $('videoProgressTicker').set( 'text', '.' );
  else
    $('videoProgressTicker').appendText( '.' );
}

function generateVideoResponse( respObj, respText ) {
  var output = 'Video Generation Failed';
  if ( respObj.result == 'Ok' ) {
    output = '';
    for (i = 0; i < respObj.response.length; i++) {
        output += respObj.response[i] + '\n';
    }
  }
  $('videoProgress').addClass( 'hidden' );
  $('result').set( 'text', output );
}

function generateVideo( form ) {
  var parms = 'view=request&request=event&action=video';
  parms += '&'+$(form).toQueryString();
  var query = new Request.JSON( { url: thisUrl, method: 'post', data: parms, onSuccess: generateVideoResponse } );
  query.send();
  $('videoProgress').removeClass( 'hidden' );
  $('videoProgress').setProperty( 'class', 'warnText' );
  // $('videoProgressText').set( 'text', videoGenProgressString );
  generateVideoProgress();
  generateVideoTimer = generateVideoProgress.periodical( 500 );
}
