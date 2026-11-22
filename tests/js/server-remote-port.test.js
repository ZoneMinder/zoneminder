'use strict';

// A remote server saved without a Port must not inherit the port of the page
// it is viewed from. Run as: node tests/js/server-remote-port.test.js [web/js/Server.js]

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const file = process.argv[2] || path.join(__dirname, '../../web/js/Server.js');
const ctx = {serverId: 1, location: {protocol: 'https:', port: '8443', host: 'zm.example:8443', hostname: 'zm.example'}};
vm.createContext(ctx);
vm.runInContext(fs.readFileSync(file, 'utf8') + '\nthis.Server = Server;', ctx);

let failed = 0;
function test(name, fn) {
  try {
    fn();
    console.log('  ok ' + name);
  } catch (e) {
    console.error('  FAIL ' + name + ': ' + e.message);
    failed++;
  }
}

const remote = new ctx.Server({Id: 2, Protocol: 'https', Hostname: 'cam2.example', Port: null, PathToZMS: '/cgi-bin/nph-zms', PathToApi: '/zm/api'});
const local = new ctx.Server({Id: null, Protocol: 'https', Hostname: 'zm.example', Port: null, PathToZMS: '/cgi-bin/nph-zms', PathToApi: '/zm/api'});

test('remote urlToZMS without port', () => assert.strictEqual(remote.urlToZMS(), 'https://cam2.example/cgi-bin/nph-zms'));
test('remote urlToApi without port', () => assert.strictEqual(remote.urlToApi(), 'https://cam2.example/zm/api'));
test('remote urlToJanus without port', () => assert.strictEqual(remote.urlToJanus(), 'https://cam2.example/janus'));
test('remote with port keeps it', () => assert.strictEqual(new ctx.Server({Id: 2, Protocol: 'https', Hostname: 'c', Port: 9000, PathToZMS: '/z'}).urlToZMS(), 'https://c:9000/z'));
test('single server still uses page port', () => assert.strictEqual(local.urlToZMS(), 'https://zm.example:8443/cgi-bin/nph-zms'));
test('this server (ZM_SERVER_ID) still uses page port', () => assert.strictEqual(new ctx.Server({Id: 1, Protocol: 'https', Hostname: 'zm.example', Port: null, PathToZMS: '/z'}).urlToZMS(), 'https://zm.example:8443/z'));

process.exit(failed ? 1 : 0);
