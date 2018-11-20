
function generateVideo( form ) {
  var parms = 'view=request&request=event&action=video';
  parms += '&'+$(form).toQueryString();
  var query = new Request.JSON( { url: thisUrl, method: 'post', data: parms } );
  query.send();
  // $('videoProgress').removeClass( 'hidden' );
  // $('videoProgress').setProperty( 'class', 'warnText' );
  // $('videoProgressText').set( 'text', videoGenProgressString );
  // generateVideoProgress();
  // generateVideoTimer = generateVideoProgress.periodical( 500 );
}