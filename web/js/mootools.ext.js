/*
 * MooTools Extension script to support custom extensions to mootools
 */
var zmMooToolsVersion = '1.2.3';

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

    var requestTimeoutCount = 0;
    var debugRequestTimeouts = false;
    /*
     * Ajax class extention to allow for request timeouts
     */
    Request = Class.refactor(Request, {

        options: { /*
            onTimeout: $empty, */
            timeout: 0,
        },

        send: function( data )
        {
            if ( this.options.timeout )
            {
                if ( this.timeoutTimer )
                    this.removeTimer();
                this.timeoutTimer = this.onTimeout.delay( this.options.timeout, this );
                if ( debugRequestTimeouts )
                    console.log( "Setting timer "+this.timeoutTimer+", "+data+", "+requestTimeoutCount+" running" );
                requestTimeoutCount++;
                this.addEvent( 'onComplete', this.removeTimer );
            }
            this.previous( data );
            return( this );
        },

        cancel: function()
        {
            if ( debugRequestTimeouts )
                console.log( "Cancelling timer "+this.timeoutTimer );
            if ( debugRequestTimeouts )
                console.log( "Running "+this.running );
            this.previous();
            this.removeTimer();
            return( this );
        },

        timeout: function()
        {
            this.onTimeout();
            return( this );
        },

        onTimeout: function ()
        {
            if ( debugRequestTimeouts )
                console.log( "Timer "+this.timeoutTimer+" timed out" );
            this.cancel();
            this.fireEvent('complete').fireEvent('failure').fireEvent('timeout');
            return( this );
        },

        removeTimer: function()
        {
            if ( this.timeoutTimer )
            {
                requestTimeoutCount--;
                if ( debugRequestTimeouts )
                    console.log( "Clearing timer "+this.timeoutTimer+", "+requestTimeoutCount+" running" );
                $clear( this.timeoutTimer );
                this.timeoutTimer = null;
            }
            return( this );
        }
    });
}
