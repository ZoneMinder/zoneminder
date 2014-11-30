/**
A listener that reports test success/failure to Sauce OnDemand.

Example usage:
node interpreter.js --browser-browserName=firefox --driver-host=ondemand.saucelabs.com --driver-port=80 --browser-username=$SAUCE_USERNAME --browser-accessKey=$SAUCE_ACCESSKEY --listener=./utils/sauce_listener.js examples/tests/get.json

You can also use --listener-silent=true to prevent the default listener output from happening, just like the --silent command.
*/
var https = require('https');
var util = require('util');

function Listener(testRun, params, interpreter_module) {
  this.testRun = testRun;
  this.originalListener = params.silent ? null : interpreter_module.getInterpreterListener(testRun, params, interpreter_module);
};

Listener.prototype.startTestRun = function(testRun, info) {
  this.sessionID = testRun.wd.sessionID;
  this.username = testRun.browserOptions.username;
  this.accessKey = testRun.browserOptions.accessKey;
  if (this.originalListener) { this.originalListener.startTestRun(testRun, info); }
};

Listener.prototype.endTestRun = function(testRun, info) {
  var data = null;
  if (info.error) {
    data = JSON.stringify({'passed': info.success, 'custom-data': {'interpreter-error': util.inspect(info.error)}});
  } else {
    data = JSON.stringify({'passed': info.success});
  }
  
  var options = {
    'hostname': 'saucelabs.com',
    'port': 443,
    'path': '/rest/v1/' + this.username + '/jobs/' + this.sessionID,
    'method': 'PUT',
    'auth': this.username + ':' + this.accessKey,
    'headers': { 'Content-Type': 'application/json', 'Content-Length': data.length }
  };
    
  var req = https.request(options);
  
  req.on('error', function(e) {
    console.error(e);
  });
  
  req.write(data);
  req.end();
  if (this.originalListener) { this.originalListener.endTestRun(testRun, info); }
};

Listener.prototype.startStep = function(testRun, step) {
  if (this.originalListener) { this.originalListener.startStep(testRun, step); }
};

Listener.prototype.endStep = function(testRun, step, info) {
  if (this.originalListener) { this.originalListener.endStep(testRun, step, info); }
};

exports.getInterpreterListener = function(testRun, options, interpreter_module) {
  return new Listener(testRun, options, interpreter_module);
};
