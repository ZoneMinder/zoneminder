/*
 * MooTools Extension script to support custom extensions to mootools
 */
var zmMooToolsVersion = '1.2.1';

/*
 * Firstly, lets check that mootools has been included and thus is present
 */
if ( typeof(MooTools) == "undefined" )
{
    alert( "MooTools not found! Please download from\nhttp://mootools.net and install in ZoneMinder web root." );
}
else
{
    //console.log( "Got MooTools version "+MooTools.version );

    /* Version check */
    if ( MooTools.version < zmMooToolsVersion )
    {
        alert( "MooTools version "+MooTools.version+" found.\nVersion "+zmMooToolsVersion+" required, please upgrade." );
    }

    /*
     * Ajax class extenstion to allow for request timeouts
     */
    Request.implement({
        send: function( data )
        {
            if ( this.options.timeout )
            {
                if ( this.timeoutTimer )
                    this.removeTimer();
                this.timeoutTimer = this.timedOut.delay( this.options.timeout, this );
                this.addEvent( 'onComplete', this.removeTimer );
            }
            var sender = this.get('send');
            sender.send( data );
            return( this );
        },
        timedOut: function ()
        {
            if ( this.options.onTimeout )
                this.options.onTimeout();
            this.cancel();
        },
        removeTimer: function()
        {
            $clear( this.timeoutTimer );
            this.timeoutTimer = 0;
        }
    });
}
