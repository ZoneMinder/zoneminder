'use strict';

// Server.js must bracket an IPv6 literal Hostname before appending a port.
// Run as: node tests/js/server-ipv6-hostname.test.js [path/to/Server.js]

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const file = process.argv[2] || path.join(__dirname, '../../web/js/Server.js');
const ctx = {location: {protocol: 'http:', port: '', host: 'zm', hostname: 'zm'}};
vm.createContext(ctx);
vm.runInContext(fs.readFileSync(file, 'utf8') + '\nthis.Server = Server;', ctx);

const mk = (h, p) => new ctx.Server({Id: 2, Protocol: 'http', Hostname: h, Port: p, PathToZMS: '/zms', PathToApi: '/api'});
assert.strictEqual(mk('fd00::5', 8080).url(), 'http://[fd00::5]:8080');
assert.strictEqual(mk('fd00::5', 8080).urlToZMS(), 'http://[fd00::5]:8080/zms');
assert.strictEqual(mk('fd00::5', 8080).urlToApi(), 'http://[fd00::5]:8080/api');
assert.strictEqual(mk('fd00::5', 0).urlToZMS(30002), 'http://[fd00::5]:30002/zms');
assert.strictEqual(mk('[fd00::5]', 8080).urlToZMS(), 'http://[fd00::5]:8080/zms');
assert.strictEqual(mk('cam2.example', 8080).urlToZMS(), 'http://cam2.example:8080/zms');
console.log('ok server-ipv6-hostname');
