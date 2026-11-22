'use strict';

var _createClass = function() {
  function defineProperties(target, props) {
    for (var i = 0; i < props.length; i++) {
      var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor);
    }
  } return function(Constructor, protoProps, staticProps) {
    if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor;
  };
}();

function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}

var Server = function() {
  function Server(json) {
    _classCallCheck(this, Server);

    for (var k in json) {
      this[k] = json[k];
    }
  }

  _createClass(Server, [
    {
      // The page's own port only applies to the server serving this page
      // (no Id on a single server install, or this server's ZM_SERVER_ID).
      key: 'isLocal',
      value: function isLocal() {
        return !this.Id || (typeof serverId !== 'undefined' && this.Id == serverId);
      }
    },
    {
      key: 'urlHost',
      value: function urlHost() {
        const host = String(this.Hostname);
        // An IPv6 literal must be bracketed to be followed by a port.
        return (host.indexOf(':') !== -1 && host.charAt(0) !== '[') ? '[' + host + ']' : host;
      }
    },
    {
      key: 'url',
      value: function url() {
        const port = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;

        return location.protocol + '//' + this.urlHost() + (port ? ':' + port : (this.Port ? ':' + this.Port : (this.isLocal() && location.port ? ':' + location.port : ''))) + (this.PathPrefix && this.PathPrefix != 'null' ? this.PathPrefix : '');
      }
    },
    {
      key: 'urlToZMS',
      value: function urlToZMS() {
        const port = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;
        return this.Protocol + '://' + this.urlHost() + (port ? ':' + port : (this.Port ? ':' + this.Port : (this.isLocal() && location.port ? ':' + location.port : ''))) + (this.PathToZMS && this.PathToZMS != 'null' ? this.PathToZMS : '');
      }
    },
    {
      key: 'urlToApi',
      value: function urlToApi() {
        const port = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;
        const protocol = (location.protocol == 'https:' ? 'https:' : this.Protocol + ':');
        const path = (this.PathToApi && (this.PathToApi != 'null')) ? this.PathToApi : '';
        // Single-server: match browser's host:port (this.Hostname/Port may be wrong behind a proxy).
        if (!this.Id) {
          return protocol + '//' + (port ? location.hostname + ':' + port : location.host) + path;
        }
        return protocol + '//' + this.urlHost() + (port ? ':' + port : (this.Port ? ':' + this.Port : (this.isLocal() && location.port ? ':' + location.port : ''))) + path;
      }
    },
    {
      key: 'getFromApi',
      value: function getFromApi() {
        const url = this.urlToApi() + (arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '');
        return fetch(appendQuery(zmAuth.appendTo(url), arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : ''));
      }
    },
    {
      key: 'urlToJanus',
      value: function urlToJanus() {
        const port = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;
        return (location.protocol=='https:'? 'https:' : this.Protocol+':') + '//' + this.urlHost() + (port ? ':' + port : (this.Port ? ':' + this.Port : (this.isLocal() && location.port ? ':' + location.port : ''))) + '/janus';
      }
    }
  ]);

  return Server;
}();
