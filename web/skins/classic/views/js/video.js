function deleteVideo( index )
{
    window.location.replace( thisUrl+'?view='+currentView+'&eid='+eventId+'&deleteIndex='+index );
}

function downloadVideo( index )
{
    window.location.replace( thisUrl+'?view='+currentView+'&eid='+eventId+'&downloadIndex='+index );
}

var generateVideoTimer = null;

function generateVideoProgress()
{
    var tickerText = $('videoProgressTicker').getText();
    if ( tickerText.length < 1 || tickerText.length > 4 )
        $('videoProgressTicker').setText( '.' );
    else
        $('videoProgressTicker').appendText( '.' );
}

function generateVideoResponse( respText )
{
    if ( respText == 'Ok' )
        return;
    var response = Json.evaluate( respText );

    window.location.replace( thisUrl+'?view='+currentView+'&eid='+eventId+'&generated='+((response.result=='Ok')?1:0) );
}

function generateVideo( form )
{
    var parms = 'view=request&request=event&action=video';
    parms += '&'+$(form).toQueryString();
    var query = new Ajax( thisUrl, { method: 'post', data: parms, onComplete: generateVideoResponse } );
    query.request();
    $('videoProgress').removeClass( 'hidden' );
    $('videoProgress').setProperty( 'class', 'warnText' );
    $('videoProgressText').setText( videoGenProgressString );
    generateVideoProgress();
    generateVideoTimer = generateVideoProgress.periodical( 500 );
}
