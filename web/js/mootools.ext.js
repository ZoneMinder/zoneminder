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
            this.parent( data );
            return( this );
        },
        timedOut: function ()
        {
            this.cancel();
            if ( this.options.onTimeout )
                this.options.onTimeout();
            return( this );
        },
        removeTimer: function()
        {
            $clear( this.timeoutTimer );
            this.timeoutTimer = 0;
            return( this );
        }
    });
}

/*
 *  class: CSS
 * 
 *      A class that builds stylesheets.
 *      <http://www.revnode.com/oss/css/>
 * 
 *  license:
 * 
 *      MIT-style license.
 * 
 *  author:
 * 
 *      Marat A. Denenberg, <marat at revnode dot com>
 * 
 *  copyright:
 * 
 *      copyright (c) 2008 revnode, <http://www.revnode.com/>
 * 
 *  inspiration:
 * 
 *      horseweapon on the mootools forum, <http://forum.mootools.net/viewtopic.php?id=6635>
 * 
 *  links:
 * 
 *      CSS implementation by popular browsers:
 * 
 *      <http://developer.apple.com/documentation/AppleApplications/Reference/SafariCSSRef/index.html>
 *      <http://www.opera.com/docs/specs/css/>
 *      <http://developer.mozilla.org/en/docs/Mozilla_CSS_support_chart>
 *      <http://developer.mozilla.org/en/docs/CSS_Reference:Mozilla_Extensions>
 *      <http://msdn2.microsoft.com/en-us/library/ms531209.aspx>
 * 
 *  changelog:
 * 
 *      added check_rule
 *      cleaned up code, fixed bugs, Safari support
 * 
 */
var CSS = new Class({
    
    local:
    {
        self:               'CSS',
        limited:            ['dpi', 'border-radius'],
        _rule:              ''
    },
    
    options:
    {
        rules:              {}
    },
    
    xhtml:
    {
        _style:             null
    },
    
    initialize:             function(options)
    {
        this.local = $merge(this.local, this.options, options, this.xhtml);
    },
    
    destroy:                function()
    {
        if(this.local._style) this.local._style.destroy();
    },
    
    refresh:                function()
    {
        var text = '';
        Hash.each(this.local.rules, function(rule, selector)
        {
            this.local._rule = '';
            Hash.each(rule, this._glue, this);
            text += (this.local._rule == '' ? '' : selector + '\n{\n' + this.local._rule + '}\n');
        }, this);
        
        this.destroy();
        this.local._style = new Element('style').set('type', 'text/css').inject(document.head);
        
        switch(Browser.Engine.name)
        {
            case 'trident':
                this.local._style.styleSheet.cssText = text;
                break;
            
            default:
                this.local._style.set('text', text);
                break;
        }
        
        return this;
    },
    
    _glue:                  function(value, property)
    {
        if(this[Browser.Engine.name + '_' + property])
        {
            var pair;
            if(pair = this[Browser.Engine.name + '_' + property](value, property))
            {
                this.local._rule += '\t' + pair[0] + ':' + pair[1] + ';\n';
            }
        }
        else if(!this.local.limited.contains(property))
        {
            this.local._rule += '\t' + property + ':' + value + ';\n';
        }
    },
    
    add_prop:               function(selector, property, value)
    {
        var rules = {}; rules[selector] = {}; rules[selector][property] = value;
        return this.add_rules(rules);
    },
    
    add_rule:               function(selector, properties)
    {
        var rules = {}; rules[selector] = properties;
        return this.add_rules(rules);
    },
    
    add_rules:              function(rules)
    {
        this.local.rules = $merge(this.local.rules, rules);
        return this;
    },
    
    remove_prop:            function(selector, property)
    {
        delete this.local.rules[selector][property];
        return this;
    },
    
    remove_rule:            function(selector)
    {
        delete this.local.rules[selector];
        return this;
    },
    
    remove_rules:           function(selectors)
    {
        if(selectors)
        {
            selectors.each(this.remove_rule, this);
        }
        else
        {
            this.local.rules = {};
        }
        return this;
    },
    
    check_rule:             function(selector)
    {
        return $defined(this.local.rules[selector]);
    }
    
});

CSS.implement({
    
    // ### TRIDENT ###
        
        'trident_opacity':      function(value, property)
        {
            return ['filter', 'alpha(opacity=' + (value * 100) + ')'];
        },
        
        'trident_dpi':          function(value, property)
        {
            if($defined(window.screen.deviceXDPI))
            {
                return ['font-size', ((96 / window.screen.deviceXDPI) * value).round() + '%'];
            }
            else
            {
                return ['font-size', value + '%'];
            }
        },

    // ### GECKO ###
    
        'gecko_border-radius':  function(value, property)
        {
            return ['-moz-' + property, value];
        },
    
    // ### WEBKIT ###
        
        'webkit_border-radius': function(value, property)
        {
            return ['-webkit-' + property, value];
        }
    
    // ### PRESTO ###
    
});
