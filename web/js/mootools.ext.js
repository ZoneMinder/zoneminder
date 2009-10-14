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
                this.addEvent( 'onComplete', this.removeTimer );
            }
            this.previous( data );
            return( this );
        },

        timeout: function()
        {
            this.onTimeout();
        },

        onTimeout: function ()
        {
            this.cancel();
            this.fireEvent('complete').fireEvent('failure').fireEvent('timeout');
        },

        removeTimer: function()
        {
            $clear( this.timeoutTimer );
            this.timeoutTimer = null;
            return( this );
        }
    });
}
