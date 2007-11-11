/*
 * MooTools Extension script to support custom extensions to mootools
 */
var zmMooToolsVersion = '1.00';

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
     * Element class extension to add getAncestor function to allow searches
     * up the DOM tree for ancestor element of give type, and class optionally
     */
    Element.extend({
            getAncestor: function( tagName, className )
        {
            if ( !tagName )
                return( null );
            tagName = tagName.toLowerCase();
            var ancestor = this;
            var ancestorTag = null;
            while( (ancestor = $(ancestor).getParent()) && $(ancestor) != document )
            {
                if ( $(ancestor).getTag() != tagName )
                    continue;
                if ( className && !$(ancestor).hasClass( className ) )
                    continue;
                return( $(ancestor) );
            }
            return( null );
            }
    });

    /*
     * Ajax class extenstion to allow for request timeouts
     */
    Ajax = Ajax.extend({
        request: function( data )
        {
            if ( this.options.timeout )
            {
                if ( this.timeoutTimer )
                {
                    this.removeTimer();
                }
                this.timeoutTimer = window.setTimeout( this.callTimeout.bindAsEventListener(this), this.options.timeout );
                this.addEvent( 'onComplete', this.removeTimer );
            }
            this.parent( data );
        },

        callTimeout: function ()
        {
            this.transport.abort();
            this.onFailure();
            if ( this.options.onTimeout )
            {
                this.options.onTimeout();
            }
        },

        removeTimer: function()
        {
            window.clearTimeout( this.timeoutTimer );
            this.timeoutTimer = 0;
        }
    });
}
