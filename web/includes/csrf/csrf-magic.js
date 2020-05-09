/**
 * @file
 *
 * Rewrites XMLHttpRequest to automatically send CSRF token with it. In theory
 * plays nice with other JavaScript libraries, needs testing though.
 */

// Here are the basic overloaded method definitions
// The wrapper must be set BEFORE onreadystatechange is written to, since
// a bug in ActiveXObject prevents us from properly testing for it.
CsrfMagic = function(real) {
  // try to make it ourselves, if you didn't pass it
  if (!real) try {real = new XMLHttpRequest;} catch (e) {;}
  if (!real) try {real = new ActiveXObject('Msxml2.XMLHTTP');} catch (e) {;}
  if (!real) try {real = new ActiveXObject('Microsoft.XMLHTTP');} catch (e) {;}
  if (!real) try {real = new ActiveXObject('Msxml2.XMLHTTP.4.0');} catch (e) {;}
  this.csrf = real;
  // properties
  var csrfMagic = this;
  real.onreadystatechange = function() {
    csrfMagic._updateProps();
    return csrfMagic.onreadystatechange ? csrfMagic.onreadystatechange() : null;
  };
  csrfMagic._updateProps();
};

CsrfMagic.prototype = {

  open: function(method, url, async, username, password) {
    if (method == 'POST') this.csrf_isPost = true;
    // deal with Opera bug, thanks jQuery
    if (username) return this.csrf_open(method, url, async, username, password);
    else return this.csrf_open(method, url, async);
  },
  csrf_open: function(method, url, async, username, password) {
    if (username) return this.csrf.open(method, url, async, username, password);
    else return this.csrf.open(method, url, async);
  },

  send: function(data) {
    if (!this.csrf_isPost) return this.csrf_send(data);
    prepend = csrfMagicName + '=' + csrfMagicToken + '&';
    //    XXX: Removed to eliminate 'Refused to set unsafe header "Content-length" ' errors in modern browsers
    //    if (this.csrf_purportedLength === undefined) {
    //        this.csrf_setRequestHeader("Content-length", this.csrf_purportedLength + prepend.length);
    //        delete this.csrf_purportedLength;
    //    }
    delete this.csrf_isPost;
    return this.csrf_send(prepend + data);
  },
  csrf_send: function(data) {
    return this.csrf.send(data);
  },

  setRequestHeader: function(header, value) {
    // We have to auto-set this at the end, since we don't know how long the
    // nonce is when added to the data.
    if (this.csrf_isPost && header == "Content-length") {
      this.csrf_purportedLength = value;
      return;
    }
    return this.csrf_setRequestHeader(header, value);
  },
  csrf_setRequestHeader: function(header, value) {
    return this.csrf.setRequestHeader(header, value);
  },

  abort: function() {
    return this.csrf.abort();
  },
  getAllResponseHeaders: function() {
    return this.csrf.getAllResponseHeaders();
  },
  getResponseHeader: function(header) {
    return this.csrf.getResponseHeader(header);
  } // ,
};

// proprietary
CsrfMagic.prototype._updateProps = function() {
  this.readyState = this.csrf.readyState;
  if (this.readyState == 4) {
    this.responseText = this.csrf.responseText;
    this.responseXML  = this.csrf.responseXML;
    this.status       = this.csrf.status;
    this.statusText   = this.csrf.statusText;
  }
};

CsrfMagic.process = function(base) {
  if ( typeof base == 'object' ) {
    base[csrfMagicName] = csrfMagicToken;
    return base;
  }
  var prepend = csrfMagicName + '=' + csrfMagicToken;
  if ( base ) return prepend + '&' + base;
  return prepend;
};

// callback function for when everything on the page has loaded
CsrfMagic.end = function() {
  // This rewrites forms AGAIN, so in case buffering didn't work this
  // certainly will.
  forms = document.getElementsByTagName('form');
  for (var i = 0; i < forms.length; i++) {
    form = forms[i];
    if (form.method.toUpperCase() !== 'POST') continue;
    if (form.elements[csrfMagicName]) continue;
    var input = document.createElement('input');
    input.setAttribute('name', csrfMagicName);
    input.setAttribute('value', csrfMagicToken);
    input.setAttribute('type', 'hidden');
    form.appendChild(input);
  }
};

// Sets things up for Mozilla/Opera/nice browsers
// We very specifically match against Internet Explorer, since they haven't
// implemented prototypes correctly yet.
if ( window.XMLHttpRequest && window.XMLHttpRequest.prototype && '\v' != 'v' ) {
  var x = XMLHttpRequest.prototype;
  var c = CsrfMagic.prototype;

  // Save the original functions
  x.csrf_open = x.open;
  x.csrf_send = x.send;
  x.csrf_setRequestHeader = x.setRequestHeader;

  // Notice that CsrfMagic is itself an instantiatable object, but only
  // open, send and setRequestHeader are necessary as decorators.
  x.open = c.open;
  x.send = c.send;
  x.setRequestHeader = c.setRequestHeader;
} else {
  // The only way we can do this is by modifying a library you have been
  // using. We support YUI, script.aculo.us, prototype, MooTools,
  // jQuery, Ext and Dojo.
  if ( window.jQuery ) {
    // jQuery didn't implement a new XMLHttpRequest function, so we have
    // to do this the hard way.
    jQuery.csrf_ajax = jQuery.ajax;
    jQuery.ajax = function( s ) {
      if (s.type && s.type.toUpperCase() == 'POST') {
        s = jQuery.extend(true, s, jQuery.extend(true, {}, jQuery.ajaxSettings, s));
        if ( s.data && s.processData && typeof s.data != "string" ) {
          s.data = jQuery.param(s.data);
        }
        s.data = CsrfMagic.process(s.data);
      }
      return jQuery.csrf_ajax(s);
    };
  }
  if ( window.Prototype ) {
    // This works for script.aculo.us too
    Ajax.csrf_getTransport = Ajax.getTransport;
    Ajax.getTransport = function() {
      return new CsrfMagic(Ajax.csrf_getTransport());
    };
  }
  if ( window.MooTools ) {
    Browser.csrf_Request = Browser.Request;
    Browser.Request = function() {
      return new CsrfMagic(Browser.csrf_Request());
    };
  }
  if ( window.YAHOO ) {
    // old YUI API
    YAHOO.util.Connect.csrf_createXhrObject = YAHOO.util.Connect.createXhrObject;
    YAHOO.util.Connect.createXhrObject = function(transaction) {
      obj = YAHOO.util.Connect.csrf_createXhrObject(transaction);
      obj.conn = new CsrfMagic(obj.conn);
      return obj;
    };
  }
  if ( window.Ext ) {
    // Ext can use other js libraries as loaders, so it has to come last
    // Ext's implementation is pretty identical to Yahoo's, but we duplicate
    // it for comprehensiveness's sake.
    Ext.lib.Ajax.csrf_createXhrObject = Ext.lib.Ajax.createXhrObject;
    Ext.lib.Ajax.createXhrObject = function(transaction) {
      obj = Ext.lib.Ajax.csrf_createXhrObject(transaction);
      obj.conn = new CsrfMagic(obj.conn);
      return obj;
    };
  }
  if ( window.dojo ) {
    // NOTE: this doesn't work with latest dojo
    dojo.csrf__xhrObj = dojo._xhrObj;
    dojo._xhrObj = function() {
      return new CsrfMagic(dojo.csrf__xhrObj());
    };
  }
};
