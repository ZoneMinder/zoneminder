//
// ZoneMinder base static javascript file, $Date$, $Revision$
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

//
// This file should only contain static JavaScript and no php.
// Use skin.js.php for JavaScript that need pre-processing
//

var popupOptions = "resizable,scrollbars,status=no,toolbar=yes";

function checkSize() {
  if ( 0 ) {
    if (window.outerHeight) {
      var w = window.outerWidth;
      var prevW = w;
      var h = window.outerHeight;
      var prevH = h;
      if (h > screen.availHeight) {
        h = screen.availHeight;
      }
      if (w > screen.availWidth) {
        w = screen.availWidth;
      }
      if (w != prevW || h != prevH) {
        window.resizeTo(w, h);
      }
    }
  }
}

// Deprecated
function newWindow( url, name, width, height ) {
  window.open( url, name, popupOptions+",width="+width+",height="+height );
}

function getPopupSize( tag, width, height ) {
  if ( typeof popupSizes == 'undefined' ) {
    Error("Can't find any window sizes");
    return {'width': 0, 'height': 0};
  }
  var popupSize = Object.clone(popupSizes[tag]);
  if ( !popupSize ) {
    Error("Can't find window size for tag '"+tag+"'");
    return {'width': 0, 'height': 0};
  }
  if ( popupSize.width && popupSize.height ) {
    if ( width || height ) {
      Warning("Ignoring passed dimensions "+width+"x"+height+" when getting popup size for tag '"+tag+"'");
    }
    return popupSize;
  }
  if ( popupSize.addWidth ) {
    popupSize.width = popupSize.addWidth;
    if ( !width ) {
      Error("Got addWidth but no passed width when getting popup size for tag '"+tag+"'");
    } else {
      popupSize.width += parseInt(width);
    }
  } else if ( width ) {
    popupSize.width = width;
    Error("Got passed width but no addWidth when getting popup size for tag '"+tag+"'");
  }
  if ( popupSize.minWidth && popupSize.width < popupSize.minWidth ) {
    Warning("Adjusting to minimum width when getting popup size for tag '"+tag+"'");
    popupSize.width = popupSize.minWidth;
  }
  if ( popupSize.addHeight ) {
    popupSize.height = popupSize.addHeight;
    if ( !height ) {
      Error("Got addHeight but no passed height when getting popup size for tag '"+tag+"'");
    } else {
      popupSize.height += parseInt(height);
    }
  } else if ( height ) {
    popupSize.height = height;
    Error("Got passed height but no addHeight when getting popup size for tag '"+tag+"'");
  }
  if ( popupSize.minHeight && ( popupSize.height < popupSize.minHeight ) ) {
    Warning("Adjusting to minimum height ("+popupSize.minHeight+") when getting popup size for tag '"+tag+"' because calculated height is " + popupSize.height);
    popupSize.height = popupSize.minHeight;
  }
  return popupSize;
}

function zmWindow(sub_url) {
  var zmWin = window.open( 'https://www.zoneminder.com'+(sub_url?sub_url:''), 'ZoneMinder' );
  if ( ! zmWin ) {
    // if popup blocking is enabled, the popup won't be defined.
    console.log("Please disable popup blocking.");
  } else {
    zmWin.focus();
  }
}

function createPopup( url, name, tag, width, height ) {
  var popupSize = getPopupSize( tag, width, height );
  var popupDimensions = "";
  if ( popupSize.width > 0 ) {
    popupDimensions += ",width="+popupSize.width;
  }
  if ( popupSize.height > 0 ) {
    popupDimensions += ",height="+popupSize.height;
  }
  var popup = window.open( url+"&popup=1", name, popupOptions+popupDimensions );
  if ( ! popup ) {
    // if popup blocking is enabled, the popup won't be defined.
    console.log("Please disable popup blocking.");
  } else {
    popup.focus();
  }
}

// Polyfill for NodeList.prototype.forEach on IE.
if (window.NodeList && !NodeList.prototype.forEach) {
  NodeList.prototype.forEach = Array.prototype.forEach;
}

window.addEventListener("DOMContentLoaded", function onSkinDCL() {
  document.querySelectorAll("form.validateFormOnSubmit").forEach(function(el) {
    el.addEventListener("submit", function onSubmit(evt) {
      if (!validateForm(this)) {
        evt.preventDefault();
      }
    });
  });

  document.querySelectorAll(".popup-link").forEach(function(el) {
    el.addEventListener("click", function onClick(evt) {
      var el = this;
      var url;
      if ( el.hasAttribute("href") ) {
        // <a>
        url = el.getAttribute("href");
      } else {
        // buttons
        url = el.getAttribute("data-url");
      }
      var name = el.getAttribute("data-window-name");
      var tag = el.getAttribute("data-window-tag");
      var width = el.getAttribute("data-window-width");
      var height = el.getAttribute("data-window-height");
      evt.preventDefault();
      createPopup(url, name, tag, width, height);
    });
  });

  document.querySelectorAll(".tabList a").forEach(function addOnClick(el) {
    el.addEventListener("click", submitTab);
  });

  // 'data-on-click-this' calls the global function in the attribute value with the element when a click happens.
  document.querySelectorAll("a[data-on-click-this], button[data-on-click-this], input[data-on-click-this]").forEach(function attachOnClick(el) {
    var fnName = el.getAttribute("data-on-click-this");
    if ( !window[fnName] ) {
      console.error("Nothing found to bind to " + fnName + " on element " + el.name);
      return;
    }
    el.onclick = window[fnName].bind(el, el);
  });

  // 'data-on-click' calls the global function in the attribute value with no arguments when a click happens.
  document.querySelectorAll("i[data-on-click], a[data-on-click], button[data-on-click], input[data-on-click]").forEach(function attachOnClick(el) {
    var fnName = el.getAttribute("data-on-click");
    if ( !window[fnName] ) {
      console.error("Nothing found to bind to " + fnName + " on element " + el.name);
      return;
    }

    el.onclick = function(ev) {
      window[fnName](ev);
    };
  });

  // 'data-on-click-true' calls the global function in the attribute value with no arguments when a click happens.
  document.querySelectorAll("a[data-on-click-true], button[data-on-click-true], input[data-on-click-true]").forEach(function attachOnClick(el) {
    var fnName = el.getAttribute("data-on-click-true");
    if ( !window[fnName] ) {
      console.error("Nothing found to bind to " + fnName);
      return;
    }
    el.onclick = function() {
      window[fnName](true);
    };
  });

  // 'data-on-change-this' calls the global function in the attribute value with the element when a change happens.
  document.querySelectorAll("select[data-on-change-this], input[data-on-change-this]").forEach(function attachOnChangeThis(el) {
    var fnName = el.getAttribute("data-on-change-this");
    if ( !window[fnName] ) {
      console.error("Nothing found to bind to " + fnName);
      return;
    }
    el.onchange = window[fnName].bind(el, el);
  });

  // 'data-on-change' adds an event listener for the global function in the attribute value when a change happens.
  document.querySelectorAll("select[data-on-change], input[data-on-change]").forEach(function attachOnChange(el) {
    var fnName = el.getAttribute("data-on-change");
    if ( !window[fnName] ) {
      console.error("Nothing found to bind to " + fnName);
      return;
    }
    el.onchange = window[fnName];
  });

  // 'data-on-input' adds an event listener for the global function in the attribute value when an input happens.
  document.querySelectorAll("input[data-on-input]").forEach(function(el) {
    var fnName = el.getAttribute("data-on-input");
    if ( !window[fnName] ) {
      console.error("Nothing found to bind to " + fnName);
      return;
    }
    el.oninput = window[fnName];
  });

  // 'data-on-input-this' calls the global function in the attribute value with the element when an input happens.
  document.querySelectorAll("input[data-on-input-this]").forEach(function(el) {
    var fnName = el.getAttribute("data-on-input-this");
    if ( !window[fnName] ) {
      console.error("Nothing found to bind to " + fnName);
      return;
    }
    el.onchange = window[fnName].bind(el, el);
  });
});

function createEventPopup( eventId, eventFilter, width, height ) {
  var url = '?view=event&eid='+eventId;
  if ( eventFilter ) {
    url += eventFilter;
  }
  var name = 'zmEvent';
  var popupSize = getPopupSize( 'event', width, height );
  var popup = window.open( url, name, popupOptions+",width="+popupSize.width+",height="+popupSize.height );
  if ( ! popup ) {
    // if popup blocking is enabled, the popup won't be defined.
    console.log("Please disable popup blocking.");
  } else {
    popup.focus();
  }
}

function createFramesPopup( eventId, width, height ) {
  var url = '?view=frames&eid='+eventId;
  var name = 'zmFrames';
  var popupSize = getPopupSize( 'frames', width, height );
  var popup = window.open( url, name, popupOptions+",width="+popupSize.width+",height="+popupSize.height );
  if ( ! popup ) {
    // if popup blocking is enabled, the popup won't be defined.
    console.log("Please disable popup blocking.");
  } else {
    popup.focus();
  }
}

function createFramePopup( eventId, frameId, width, height ) {
  var url = '?view=frame&eid='+eventId+'&fid='+frameId;
  var name = 'zmFrame';
  var popupSize = getPopupSize( 'frame', width, height );
  var popup = window.open( url, name, popupOptions+",width="+popupSize.width+",height="+popupSize.height );
  if ( ! popup ) {
    // if popup blocking is enabled, the popup won't be defined.
    console.log("Please disable popup blocking.");
  } else {
    popup.focus();
  }
}

function windowToFront() {
  top.window.focus();
}

function closeWindow() {
  top.window.close();
}

function refreshWindow() {
  window.location.reload( true );
}
function backWindow() {
  window.history.back();
}

function refreshParentWindow() {
  if ( refreshParent ) {
    if ( window.opener ) {
      if ( refreshParent == true ) {
        window.opener.location.reload( true );
      } else {
        window.opener.location.href = refreshParent;
      }
    }
  }
}

if ( currentView != 'none' && currentView != 'login' ) {
  $j.ajaxSetup({timeout: AJAX_TIMEOUT}); //sets timeout for all getJSON.

  $j(document).ready(function() {
    if ( $j('.navbar').length ) {
      setInterval(getNavBar, navBarRefresh);
    }
  });

  function getNavBar() {
    $j.getJSON(thisUrl + '?view=request&request=status&entity=navBar')
        .done(setNavBar)
        .fail(function(jqxhr, textStatus, error) {
          console.log("Request Failed: " + textStatus + ", " + error);
          if ( textStatus != "timeout" ) {
          // The idea is that this should only fail due to auth, so reload the page
          // which should go to login if it can't stay logged in.
            window.location.reload(true);
          }
        });
  }

  function setNavBar(data) {
    if ( data.auth ) {
      if ( data.auth != auth_hash ) {
        // Update authentication token.
        auth_hash = data.auth;
      }
    }
    $j('#reload').replaceWith(data.message);
  }
}

//Shows a message if there is an error in the streamObj or the stream doesn't exist.  Returns true if error, false otherwise.
function checkStreamForErrors( funcName, streamObj ) {
  if ( !streamObj ) {
    Error( funcName+": stream object was null" );
    return true;
  }
  if ( streamObj.result == "Error" ) {
    Error( funcName+" stream error: "+streamObj.message );
    return true;
  }
  return false;
}

function secsToTime( seconds ) {
  var timeString = "--";
  if ( seconds < 60 ) {
    timeString = seconds.toString();
  } else if ( seconds < 60*60 ) {
    var timeMins = parseInt(seconds/60);
    var timeSecs = seconds%60;
    if ( timeSecs < 10 ) {
      timeSecs = '0'+timeSecs.toString().substr( 0, 4 );
    } else {
      timeSecs = timeSecs.toString().substr( 0, 5 );
    }
    timeString = timeMins+":"+timeSecs;
  } else {
    var timeHours = parseInt(seconds/3600);
    var timeMins = (seconds%3600)/60;
    var timeSecs = seconds%60;
    if ( timeMins < 10 ) {
      timeMins = '0'+timeMins.toString().substr( 0, 4 );
    } else {
      timeMins = timeMins.toString().substr( 0, 5 );
    }
    if ( timeSecs < 10 ) {
      timeSecs = '0'+timeSecs.toString().substr( 0, 4 );
    } else {
      timeSecs = timeSecs.toString().substr( 0, 5 );
    }
    timeString = timeHours+":"+timeMins+":"+timeSecs;
  }
  return ( timeString );
}

function submitTab(evt) {
  var tab = this.getAttribute("data-tab-name");
  var form = $('contentForm');
  form.action.value = "";
  form.tab.value = tab;
  form.submit();
  evt.preventDefault();
}

function submitThisForm() {
  this.form.submit();
}

/**
 * @param {Element} headerCheckbox The select all/none checkbox that was just toggled.
 * @param {DOMString} name The name of the checkboxes to toggle.
 */
function updateFormCheckboxesByName( headerCheckbox ) {
  var name = headerCheckbox.getAttribute("data-checkbox-name");
  var form = headerCheckbox.form;
  var checked = headerCheckbox.checked;
  for (var i = 0; i < form.elements.length; i++) {
    if (form.elements[i].name.indexOf(name) == 0) {
      form.elements[i].checked = checked;
    }
  }
  setButtonStates(headerCheckbox);
}

function configureDeleteButton( element ) {
  var form = element.form;
  var checked = element.checked;
  if ( !checked ) {
    for ( var i = 0; i < form.elements.length; i++ ) {
      if ( form.elements[i].name == element.name ) {
        if ( form.elements[i].checked ) {
          checked = true;
          break;
        }
      }
    }
  }
  form.deleteBtn.disabled = !checked;
}

function confirmDelete( message ) {
  return ( confirm( message?message:'Are you sure you wish to delete?' ) );
}

if ( refreshParent ) {
  refreshParentWindow();
}

if ( focusWindow ) {
  windowToFront();
}
if ( closePopup ) {
  closeWindow();
}

window.addEventListener( 'DOMContentLoaded', checkSize );

function convertLabelFormat(LabelFormat, monitorName) {
  //convert label format from strftime to moment's format (modified from
  //https://raw.githubusercontent.com/benjaminoakes/moment-strftime/master/lib/moment-strftime.js
  //added %f and %N below (TODO: add %Q)
  var replacements = {
    'a': 'ddd',
    'A': 'dddd',
    'b': 'MMM',
    'B': 'MMMM',
    'd': 'DD',
    'e': 'D',
    'F': 'YYYY-MM-DD',
    'H': 'HH',
    'I': 'hh',
    'j': 'DDDD',
    'k': 'H',
    'l': 'h',
    'm': 'MM',
    'M': 'mm',
    'p': 'A',
    'r': 'hh:mm:ss A',
    'S': 'ss',
    'u': 'E',
    'w': 'd',
    'W': 'WW',
    'y': 'YY',
    'Y': 'YYYY',
    'z': 'ZZ',
    'Z': 'z',
    'f': 'SS',
    'N': '['+monitorName+']',
    '%': '%'};
  var momentLabelFormat = Object.keys(replacements).reduce(function(momentFormat, key) {
    var value = replacements[key];
    return momentFormat.replace('%' + key, value);
  }, LabelFormat);
  return momentLabelFormat;
}

function addVideoTimingTrack(video, LabelFormat, monitorName, duration, startTime) {
//This is a hacky way to handle changing the texttrack. If we ever upgrade vjs in a revamp replace this.  Old method preserved because it's the right way.
  var cues = vid.textTracks()[0].cues();
  var labelFormat = convertLabelFormat(LabelFormat, monitorName);
  startTime = moment(startTime);

  for ( var i = 0; i <= duration; i++ ) {
    cues[i] = {id: i, index: i, startTime: i, endTime: i+1, text: startTime.format(labelFormat)};
    startTime.add(1, 's');
  }
}
/*
var labelFormat = convertLabelFormat(LabelFormat, monitorName);
var webvttformat = 'HH:mm:ss.SSS', webvttdata="WEBVTT\n\n";

startTime = moment(startTime);

var seconds = moment({s:0}), endduration = moment({s:duration});
while(seconds.isBefore(endduration)){
  webvttdata += seconds.format(webvttformat) + " --> ";
  seconds.add(1,'s');
  webvttdata += seconds.format(webvttformat) + "\n";
  webvttdata += startTime.format(labelFormat) + "\n\n";
  startTime.add(1, 's');
}
var track = document.createElement('track');
track.kind = "captions";
track.srclang = "en";
track.label = "English";
track['default'] = true;
track.src = 'data:plain/text;charset=utf-8,'+encodeURIComponent(webvttdata);
video.appendChild(track);
}
*/

var resizeTimer;

function endOfResize(e) {
  clearTimeout(resizeTimer);
  resizeTimer = setTimeout(changeScale, 250);
}

function scaleToFit(baseWidth, baseHeight, scaleEl, bottomEl) {
  $j(window).on('resize', endOfResize); //set delayed scaling when Scale to Fit is selected
  var ratio = baseWidth / baseHeight;
  var container = $j('#content');
  var viewPort = $j(window);
  // jquery does not provide a bottom offet, and offset dows not include margins.  outerHeight true minus false gives total vertical margins.
  var bottomLoc = bottomEl.offset().top + (bottomEl.outerHeight(true) - bottomEl.outerHeight()) + bottomEl.outerHeight(true);
  var newHeight = viewPort.height() - (bottomLoc - scaleEl.outerHeight(true));
  var newWidth = ratio * newHeight;
  if (newWidth > container.innerWidth()) {
    newWidth = container.innerWidth();
    newHeight = newWidth / ratio;
  }
  var autoScale = Math.round(newWidth / baseWidth * SCALE_BASE);
  var scales = $j('#scale option').map(function() {
    return parseInt($j(this).val());
  }).get();
  scales.shift();
  var closest;
  $j(scales).each(function() { //Set zms scale to nearest regular scale.  Zoom does not like arbitrary scale values.
    if (closest == null || Math.abs(this - autoScale) < Math.abs(closest - autoScale)) {
      closest = this.valueOf();
    }
  });
  autoScale = closest;
  return {width: Math.floor(newWidth), height: Math.floor(newHeight), autoScale: autoScale};
}

function setButtonState(element_id, butClass) {
  var element = $(element_id);
  if ( element ) {
    element.className = butClass;
    if (butClass == 'unavail' || (butClass == 'active' && (element.id == 'pauseBtn' || element.id == 'playBtn'))) {
      element.disabled = true;
    } else {
      element.disabled = false;
    }
  } else {
    console.log('Element was null or not found in setButtonState. id:'+element_id);
  }
}
