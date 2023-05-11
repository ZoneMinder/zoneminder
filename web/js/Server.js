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
      key: 'url',
      value: function url() {
        const port = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;

        return location.protocol + '//' + this.Hostname + (port ? ':' + port : '') + (this.PathPrefix && this.PathPrefix != 'null' ? this.PathPrefix : '');
      }
    },
    {
      key: 'urlToZMS',
      value: function urlToZMS() {
        const port = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;
        return this.Protocol + '://' + this.Hostname + (port ? ':' + port : '') + (this.PathToZMS && this.PathToZMS != 'null' ? this.PathToZMS : '');
      }
    },
    {
      key: 'urlToApi',
      value: function urlToApi() {
        const port = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;
        return (location.protocol=='https:'? 'https:' : this.Protocol+':') + '//' + this.Hostname + (port ? ':' + port : '') + ((this.PathToApi && (this.PathToApi != 'null')) ? this.PathToApi : '');
      }
    },
    {
      key: 'getFromApi',
      value: function getFromApi() {
        const url = this.urlToApi() + (arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '');
        return fetch(url + '?' + auth_relay + (arguments.length > 1 && arguments[1] !== undefined ? '&'+arguments[1] : ''));
      }
    },
    {
      key: 'urlToJanus',
      value: function urlToJanus() {
        const port = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;
        return (location.protocol=='https:'? 'https:' : this.Protocol+':') + '//' + this.Hostname + (port ? ':' + port : '') + '/janus';
      }
    }
  ]);

  return Server;
}();
