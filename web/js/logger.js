//
// ZoneMinder logger javascript file, $Date: 2011-05-27 22:24:17 +0100 (Fri, 27 May 2011) $, $Revision: 3374 $
// Copyright (C) 2001-2008 Philip Coombes
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//
$j.ajaxSetup({timeout: AJAX_TIMEOUT});
const reportLogs = (typeof(ZM_LOG_INJECT) !== 'undefined') && ((ZM_LOG_INJECT !== '0') || canEdit["System"]);

if ( !window.console ) {
  window.console =
    {
      init: function() {},
      log: function() {},
      debug: function() {},
      info: function() {},
      warn: function() {},
      error: function() {}
    };
}

if ( !console.debug ) {
  // IE8 has console but doesn't have console.debug so lets alias it.
  console.debug = console.log;
}

window.onerror = function(message, url, line) {
  logReport("ERR", message, url, line);
};

window.addEventListener("securitypolicyviolation", function logCSP(evt) {
  var level = evt.disposition == "enforce" ? "ERR" : "DBG";
  var message = evt.blockedURI + " violated CSP " + evt.violatedDirective;

  if ( evt.sample ) message += " (Sample: " + evt.sample + ")";
  logReport(level, message, evt.sourceFile, evt.lineNumber);
});

function logReport( level, message, file, line ) {
  if ( !reportLogs ) return;

  /* eslint-disable no-caller */
  if ( arguments && arguments.callee && arguments.callee.caller && arguments.callee.caller.caller && arguments.callee.caller.caller.name ) {
    message += ' - '+arguments.callee.caller.caller.name+'()';
  }

  var data = {
    view: 'request',
    request: 'log',
    task: 'create',
    level: level,
    message: encodeURIComponent(message),
    browser: browserInfo()
  };

  if ( file ) {
    data.file = file;
  } else if ( location.search ) {
    //location.search is the querystring part, so ?blah=blah but there is almost never any value to this
    data.file = location.search;
  }

  if ( line ) data.line = line;

  $j.post(thisUrl, data, null, 'json');
}

function Panic(message) {
  console.error(message);
  logReport("PNC", message);
  alert("PANIC: "+message);
}

function Fatal(message) {
  console.error(message);
  logReport( "FAT", message );
  alert( "FATAL: "+message );
}

function Error(message) {
  console.error(message);
  logReport("ERR", message);
}

function Warning(message) {
  console.warn(message);
  logReport("WAR", message);
}

function Info(message) {
  console.info(message);
  logReport("INF", message);
}

function Debug(message) {
  console.debug(message);
  //logReport("DBG", message);
}

function Dump(value, label) {
  if ( label ) console.debug(label+" => ");
  console.debug(value);
}

/*
JQuery has deprecated its browser object. This function implements our own
browser object. It strikes a compromise between importing a full browser
detection js library and using the js navigator object in its umodified form.

This function derived from the following sample on stackoverflow:
https://stackoverflow.com/a/11219680
*/

function browserInfo() {
  var browser = {};
  var nAgt = navigator.userAgent;
  var browserName = navigator.appName;
  var fullVersion = ''+parseFloat(navigator.appVersion);
  var majorVersion = parseInt(navigator.appVersion, 10);
  var nameOffset;
  var verOffset;
  var ix;

  // In Opera, the true version is after "Opera" or after "Version"
  if ((verOffset=nAgt.indexOf("Opera")) != -1) {
    browserName = "Opera";
    fullVersion = nAgt.substring(verOffset+6);
    if ((verOffset=nAgt.indexOf("Version")) != -1) {
      fullVersion = nAgt.substring(verOffset+8);
    }
  // In MSIE, the true version is after "MSIE" in userAgent
  } else if ((verOffset=nAgt.indexOf("MSIE")) != -1) {
    browserName = "Microsoft Internet Explorer";
    fullVersion = nAgt.substring(verOffset+5);
  // In Chrome, the true version is after "Chrome"
  } else if ((verOffset=nAgt.indexOf("Chrome")) != -1) {
    browserName = "Chrome";
    fullVersion = nAgt.substring(verOffset+7);
  // In Safari, the true version is after "Safari" or after "Version"
  } else if ((verOffset=nAgt.indexOf("Safari")) != -1) {
    browserName = "Safari";
    fullVersion = nAgt.substring(verOffset+7);
    if ((verOffset=nAgt.indexOf("Version")) != -1) {
      fullVersion = nAgt.substring(verOffset+8);
    }
  // In Firefox, the true version is after "Firefox"
  } else if ((verOffset=nAgt.indexOf("Firefox")) != -1) {
    browserName = "Firefox";
    fullVersion = nAgt.substring(verOffset+8);
  // In most other browsers, "name/version" is at the end of userAgent
  } else if ( (nameOffset=nAgt.lastIndexOf(' ')+1) < (verOffset=nAgt.lastIndexOf('/')) ) {
    browserName = nAgt.substring(nameOffset, verOffset);
    fullVersion = nAgt.substring(verOffset+1);
    if (browserName.toLowerCase()==browserName.toUpperCase()) {
      browserName = navigator.appName;
    }
  }

  // trim the fullVersion string at semicolon/space if present
  if ((ix=fullVersion.indexOf(";")) != -1) {
    fullVersion=fullVersion.substring(0, ix);
  }
  if ((ix=fullVersion.indexOf(" ")) != -1) {
    fullVersion=fullVersion.substring(0, ix);
  }

  var majorVersion = parseInt(''+fullVersion, 10);
  if (isNaN(majorVersion)) {
    fullVersion = ''+parseFloat(navigator.appVersion);
    majorVersion = parseInt(navigator.appVersion, 10);
  }

  // OSName variable is set as follows:
  // "Windows"    for all versions of Windows
  // "MacOS"      for all versions of Macintosh OS
  // "Linux"      for all versions of Linux
  // "UNIX"       for all other UNIX flavors
  // "Unknown OS" indicates failure to detect the OS
  var OSName="Unknown OS";
  if (navigator.appVersion.indexOf("Win") != -1) OSName="Windows";
  if (navigator.appVersion.indexOf("Mac") != -1) OSName="MacOS";
  if (navigator.appVersion.indexOf("X11") != -1) OSName="UNIX";
  if (navigator.appVersion.indexOf("Linux") != -1) OSName="Linux";

  browser.name = browserName;
  browser.version = fullVersion;
  browser.platform = OSName;

  return browser;
}
