class Server {
  constructor(json) {
    for( var k in json ) {
      this[k] = json[k];
    }
  }
  url(port=0){
    return location.protocol+'//'+this.Hostname+
      (port ? ':'+port : '') +
      ( ( this.PathPrefix && this.PathPrefix != 'null') ? this.PathPrefix : '');
  }
};
