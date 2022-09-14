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

// Globally define the icons used in the bootstrap-table top-right toolbar
var icons = {
  paginationSwitchDown: 'fa-caret-square-o-down',
  paginationSwitchUp: 'fa-caret-square-o-up',
  export: 'fa-download',
  refresh: 'fa-retweet',
  autoRefresh: 'fa-clock-o',
  advancedSearchIcon: 'fa-chevron-down',
  toggleOff: 'fa-toggle-off',
  toggleOn: 'fa-toggle-on',
  columns: 'fa-th-list',
  fullscreen: 'fa-arrows-alt',
  detailOpen: 'fa-plus',
  detailClose: 'fa-minus'
};

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

  document.querySelectorAll(".zmlink").forEach(function(el) {
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
      evt.preventDefault();
      window.location.assign(url);
    });
  });

  document.querySelectorAll(".pillList a").forEach(function addOnClick(el) {
    el.addEventListener("click", submitTab);
  });

  dataOnClickThis();
  dataOnClick();
  dataOnClickTrue();
  dataOnChangeThis();
  dataOnChange();
  dataOnInput();
  dataOnInputThis();
});

// 'data-on-click-this' calls the global function in the attribute value with the element when a click happens.
function dataOnClickThis() {
  document.querySelectorAll("a[data-on-click-this], button[data-on-click-this], input[data-on-click-this]").forEach(function attachOnClick(el) {
    var fnName = el.getAttribute("data-on-click-this");
    if ( !window[fnName] ) {
      console.error("Nothing found to bind to " + fnName + " on element " + el.name);
      return;
    }
    el.onclick = window[fnName].bind(el, el);
  });
}

// 'data-on-click' calls the global function in the attribute value with no arguments when a click happens.
function dataOnClick() {
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
  document.querySelectorAll("button[data-on-mousedown]").forEach(function(el) {
    var fnName = el.getAttribute("data-on-mousedown");
    if ( !window[fnName] ) {
      console.error("Nothing found to bind to " + fnName + " on element " + el.name);
      return;
    }

    el.onmousedown = function(ev) {
      window[fnName](ev);
    };
  });
  document.querySelectorAll("button[data-on-mouseup]").forEach(function(el) {
    var fnName = el.getAttribute("data-on-mouseup");
    if ( !window[fnName] ) {
      console.error("Nothing found to bind to " + fnName + " on element " + el.name);
      return;
    }

    el.onmouseup = function(ev) {
      window[fnName](ev);
    };
  });
}

// 'data-on-click-true' calls the global function in the attribute value with no arguments when a click happens.
function dataOnClickTrue() {
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
}

// 'data-on-change-this' calls the global function in the attribute value with the element when a change happens.
function dataOnChangeThis() {
  document.querySelectorAll("select[data-on-change-this], input[data-on-change-this]").forEach(function attachOnChangeThis(el) {
    var fnName = el.getAttribute("data-on-change-this");
    if ( !window[fnName] ) {
      console.error("Nothing found to bind to " + fnName);
      return;
    }
    el.onchange = window[fnName].bind(el, el);
  });
}

// 'data-on-change' adds an event listener for the global function in the attribute value when a change happens.
function dataOnChange() {
  document.querySelectorAll("select[data-on-change], input[data-on-change]").forEach(function attachOnChange(el) {
    var fnName = el.getAttribute("data-on-change");
    if ( !window[fnName] ) {
      console.error("Nothing found to bind to " + fnName);
      return;
    }
    el.onchange = window[fnName];
  });
}

// 'data-on-input' adds an event listener for the global function in the attribute value when an input happens.
function dataOnInput() {
  document.querySelectorAll("input[data-on-input]").forEach(function(el) {
    var fnName = el.getAttribute("data-on-input");
    if ( !window[fnName] ) {
      console.error("Nothing found to bind to " + fnName);
      return;
    }
    el.oninput = window[fnName];
  });
}

// 'data-on-input-this' calls the global function in the attribute value with the element when an input happens.
function dataOnInputThis() {
  document.querySelectorAll("input[data-on-input-this]").forEach(function(el) {
    var fnName = el.getAttribute("data-on-input-this");
    if ( !window[fnName] ) {
      console.error("Nothing found to bind to " + fnName);
      return;
    }
    el.oninput = window[fnName].bind(el, el);
  });
}

function openEvent( eventId, eventFilter ) {
  var url = '?view=event&eid='+eventId;
  if ( eventFilter ) {
    url += eventFilter;
  }
  window.location.assign(url);
}

function openFrames( eventId ) {
  var url = '?view=frames&eid='+eventId;
  window.location.assign(url);
}

function openFrame( eventId, frameId, width, height ) {
  var url = '?view=frame&eid='+eventId+'&fid='+frameId;
  window.location.assign(url);
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
    // Load the Logout and State modals into the dom
    $j('#logoutButton').click(clickLogout);
    if ( canEdit.System ) $j('#stateModalBtn').click(getStateModal);

    // Trigger autorefresh of the widget bar stats on the navbar
    if ( $j('.navbar').length ) {
      setInterval(getNavBar, navBarRefresh);
    }
    // Update zmBandwidth cookie when the user makes a selection from the dropdown
    bwClickFunction();
    // Update update reminders when the user makes a selection from the dropdown
    reminderClickFunction();
    // Manage the widget bar minimize chevron
    $j("#flip").click(function() {
      $j("#panel").slideToggle("slow");
      var flip = $j("#flip");
      if ( flip.html() == 'keyboard_arrow_up' ) {
        flip.html('keyboard_arrow_down');
        setCookie('zmHeaderFlip', 'down', 3600);
      } else {
        flip.html('keyboard_arrow_up');
        setCookie('zmHeaderFlip', 'up', 3600);
      }
    });
    // Manage the web console filter bar minimize chevron
    $j("#fbflip").click(function() {
      $j("#fbpanel").slideToggle("slow");
      var fbflip = $j("#fbflip");
      if ( fbflip.html() == 'keyboard_arrow_up' ) {
        fbflip.html('keyboard_arrow_down');
        setCookie('zmFilterBarFlip', 'down', 3600);
      } else {
        fbflip.html('keyboard_arrow_up');
        setCookie('zmFilterBarFlip', 'up', 3600);
        $j('.chosen').chosen("destroy");
        $j('.chosen').chosen();
      }
    });

    // Manage the web console filter bar minimize chevron
    $j("#mfbflip").click(function() {
      $j("#mfbpanel").slideToggle("slow");
      var mfbflip = $j("#mfbflip");
      if ( mfbflip.html() == 'keyboard_arrow_up' ) {
        mfbflip.html('keyboard_arrow_down');
        setCookie('zmMonitorFilterBarFlip', 'up', 3600);
      } else {
        mfbflip.html('keyboard_arrow_up');
        setCookie('zmMonitorFilterBarFlip', 'down', 3600);
        $j('.chosen').chosen("destroy");
        $j('.chosen').chosen();
      }
    });
    // Autoclose the hamburger button if the end user clicks outside the button
    $j(document).click(function(event) {
      var target = $j(event.target);
      var _mobileMenuOpen = $j("#main-header-nav").hasClass("show");
      if (_mobileMenuOpen === true && !target.hasClass("navbar-toggler")) {
        $j("button.navbar-toggler").click();
      }
    });
    // Manage the optionhelp links
    $j(".optionhelp").click(function(evt) {
      $j.getJSON(thisUrl + '?request=modal&modal=optionhelp&ohndx=' + evt.target.id)
          .done(optionhelpModal)
          .fail(logAjaxFail);
    });
  });

  // After retieving modal html via Ajax, this will insert it into the DOM
  function insertModalHtml(name, html) {
    var modal = $j('#' + name);

    if (modal.length) {
      modal.replaceWith(html);
    } else {
      $j("body").append(html);
    }
  }

  // Manage the modal html we received after user clicks help link
  function optionhelpModal(data) {
    insertModalHtml('optionhelp', data.html);
    $j('#optionhelp').modal('show');

    // Manage the CLOSE optionhelp modal button
    document.getElementById("ohCloseBtn").addEventListener("click", function onOhCloseClick(evt) {
      $j('#optionhelp').modal('hide');
    });
  }

  function getNavBar() {
    $j.getJSON(thisUrl + '?view=request&request=status&entity=navBar' + (auth_relay?'&'+auth_relay:''))
        .done(setNavBar)
        .fail(function(jqxhr, textStatus, error) {
          console.log("Request Failed: " + textStatus + ", " + error);
          if (error == 'Unauthorized') {
            window.location.reload(true);
          }
          if (!jqxhr.responseText) {
            console.log("No responseText in jqxhr");
            console.log(jqxhr);
            return;
          }
          console.log("Response Text: " + jqxhr.responseText.replace(/(<([^>]+)>)/gi, ''));
          if (textStatus != "timeout") {
          // The idea is that this should only fail due to auth, so reload the page
          // which should go to login if it can't stay logged in.
            window.location.reload(true);
          }
        });
  }

  function setNavBar(data) {
    if (!data) {
      console.error("No data in setNavBar");
      return;
    }
    if (data.auth) {
      if (data.auth != auth_hash) {
        console.log("Update auth_hash to "+data.auth);
        // Update authentication token.
        auth_hash = data.auth;
      }
    }
    if (data.auth_relay) {
      auth_relay = data.auth_relay;
    }
    // iterate through all the keys then update each element id with the same name
    for (var key of Object.keys(data)) {
      if ( key == "auth" ) continue;
      if ( key == "auth_relay" ) continue;
      if ( $j('#'+key).hasClass("show") ) continue; // don't update if the user has the dropdown open
      if ( $j('#'+key).length ) $j('#'+key).replaceWith(data[key]);
      if ( key == 'getBandwidthHTML' ) bwClickFunction();
    }
  }
} // end if ( currentView != 'none' && currentView != 'login' )

//Shows a message if there is an error in the streamObj or the stream doesn't exist.  Returns true if error, false otherwise.
function checkStreamForErrors(funcName, streamObj) {
  if ( !streamObj ) {
    Error(funcName+': stream object was null');
    return true;
  }
  if ( streamObj.result == "Error" ) {
    Error(funcName+' stream error: '+streamObj.message);
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
  return timeString;
}

function submitTab(evt) {
  var tab = this.getAttribute("data-tab-name");
  var form = $j('#contentForm');
  form.attr('action', '');
  form.attr('tab', tab);
  form.submit();
  evt.preventDefault();
}

function submitThisForm() {
  if ( ! this.form ) {
    console.log("No this.form.  element with onchange is not in a form");
    return;
  }
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

function setButtonState(element_id, btnClass) {
  var element = document.getElementById(element_id);
  if ( element ) {
    element.className = btnClass;
    if (btnClass == 'unavail' || (btnClass == 'active' && (element.id == 'pauseBtn' || element.id == 'playBtn'))) {
      element.disabled = true;
    } else {
      element.disabled = false;
    }
  } else {
    console.log('Element was null or not found in setButtonState. id:'+element_id);
  }
}

function setCookie(name, value, days) {
  var expires = "";
  if (days) {
    var date = new Date();
    date.setTime(date.getTime() + (days*24*60*60*1000));
    expires = "; expires=" + date.toUTCString();
  }
  document.cookie = name + "=" + (value || "") + expires + "; path=/; samesite=strict";
}

function getCookie(name) {
  var nameEQ = name + "=";
  var ca = document.cookie.split(';');
  for (var i=0; i < ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0)==' ') c = c.substring(1, c.length);
    if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
  }
  return null;
}

function delCookie(name) {
  document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
}

function bwClickFunction() {
  $j('.bwselect').click(function() {
    var bwval = $j(this).data('pdsa-dropdown-val');
    setCookie("zmBandwidth", bwval, 3600);
    getNavBar();
  });
}

function reminderClickFunction() {
  $j("#dropdown_reminder a").click(function() {
    var option = $j(this).data('pdsa-dropdown-val');
    $j.getJSON(thisUrl + '?view=version&action=version&option=' + option)
        .done(window.location.reload(true)) //Do a full refresh to update ZM_DYN_LAST_VERSION
        .fail(logAjaxFail);
  });
}

// Load then show the "You No Permission" error modal
function enoperm() {
  $j.getJSON(thisUrl + '?request=modal&modal=enoperm')
      .done(function(data) {
        insertModalHtml('ENoPerm', data.html);
        $j('#ENoPerm').modal('show');

        // Manage the CLOSE optionhelp modal button
        document.getElementById("enpCloseBtn").addEventListener("click", function onENPCloseClick(evt) {
          $j('#ENoPerm').modal('hide');
        });
      })
      .fail(logAjaxFail);
}

function getLogoutModal() {
  $j.getJSON(thisUrl + '?request=modal&modal=logout')
      .done(function(data) {
        insertModalHtml('modalLogout', data.html);
        manageModalBtns('modalLogout');
        clickLogout();
      })
      .fail(logAjaxFail);
}
function clickLogout() {
  const modalLogout = $j('#modalLogout');

  if (!modalLogout.length) {
    getLogoutModal();
    return;
  }
  modalLogout.modal('show');
}

function getStateModal() {
  $j.getJSON(thisUrl + '?request=modal&modal=state')
      .done(function(data) {
        insertModalHtml('modalState', data.html);
        $j('#modalState').modal('show');
        manageStateModalBtns();
      })
      .fail(logAjaxFail);
}

function manageStateModalBtns() {
  // Enable or disable the Delete button depending on the selected run state
  $j("#runState").change(function() {
    runstate = $j(this).val();

    if ( (runstate == 'stop') || (runstate == 'restart') || (runstate == 'start') || (runstate == 'default') ) {
      $j("#btnDelete").prop("disabled", true);
    } else {
      $j("#btnDelete").prop("disabled", false);
    }
  });

  // Enable or disable the Save button when entering a new state
  $j("#newState").keyup(function() {
    length = $j(this).val().length;
    if ( length < 1 ) {
      $j("#btnSave").prop("disabled", true);
    } else {
      $j("#btnSave").prop("disabled", false);
    }
  });


  // Delete a state
  $j("#btnDelete").click(function() {
    stateStuff('delete', $j("#runState").val());
  });


  // Save a new state
  $j("#btnSave").click(function() {
    stateStuff('save', undefined, $j("#newState").val());
  });

  // Change state
  $j("#btnApply").click(function() {
    stateStuff('state', $j("#runState").val());
  });
}

function stateStuff(action, runState, newState) {
  // the state action will redirect to console
  var formData = {
    'view': 'state',
    'action': action,
    'apply': 1,
    'runState': runState,
    'newState': newState
  };

  $j("#pleasewait").toggleClass("hidden");

  $j.ajax({
    type: 'POST',
    url: thisUrl,
    data: formData,
    dataType: 'html',
    timeout: 0
  }).done(function(data) {
    location.reload();
  });
}

function logAjaxFail(jqxhr, textStatus, error) {
  console.log("Request Failed: " + textStatus + ", " + error);
  if ( ! jqxhr.responseText ) {
    console.log("Ajax request failed.  No responseText.  jqxhr follows:");
    console.log(jqxhr);
    return;
  }
  var responseText = jqxhr.responseText.replace(/(<([^>]+)>)/gi, '').trim(); // strip any html or whitespace from the response
  if ( responseText ) console.log("Response Text: " + responseText);
}

// Load the Modal HTML via Ajax call
function getModal(id, parameters, buttonconfig=null) {
  $j.getJSON(thisUrl + '?request=modal&modal='+id+'&'+parameters)
      .done(function(data) {
        if ( !data ) {
          console.error("Get modal returned no data");
          return;
        }

        insertModalHtml(id, data.html);
        buttonconfig ? buttonconfig() : manageModalBtns(id);
        modal = $j('#'+id+'Modal');
        if ( ! modal.length ) {
          console.log('No modal found');
        }
        $j('#'+id+'Modal').modal('show');
      })
      .fail(logAjaxFail);
}

function showModal(id, buttonconfig=null) {
  var div = $j('#'+id+'Modal');
  if ( ! div.length ) {
    getModal(id, buttonconfig);
  }
  div.modal('show');
}

function manageModalBtns(id) {
  // Manage the CANCEL modal button, note data-dismiss="modal" would work better
  var cancelBtn = document.getElementById(id+"CancelBtn");
  if ( cancelBtn ) {
    document.getElementById(id+"CancelBtn").addEventListener('click', function onCancelClick(evt) {
      $j('#'+id).modal('hide');
    });
  }

  // 'data-on-click-this' calls the global function in the attribute value with the element when a click happens.
  document.querySelectorAll('#'+id+'Modal button[data-on-click]').forEach(function attachOnClick(el) {
    var fnName = el.getAttribute('data-on-click');
    if ( !window[fnName] ) {
      console.error('Nothing found to bind to ' + fnName + ' on element ' + el.name);
      return;
    } else {
      console.log("Setting onclick for " + el.name);
    }
    el.onclick = window[fnName].bind(el, el);
  });
}

function bindButton(selector, action, data, func) {
  var elements = $j(selector);
  if ( !elements.length ) {
    console.log("Nothing found for " + selector);
    return;
  }
  elements.on(action, data, func);
}


function human_filesize(size, precision = 2) {
  var units = Array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
  var step = 1024;
  var i = 0;
  while ((size / step) > 0.9) {
    size = size / step;
    i++;
  }
  return (Math.round(size*(10^precision))/(10^precision))+units[i];
}

function startDownload( exportFile ) {
  console.log("Starting download from " + exportFile);
  window.location.replace( exportFile );
}

function exportResponse(data, responseText) {
  console.log('exportResponse data: ' + JSON.stringify(data));

  var generated = (data.result=='Ok') ? 1 : 0;
  //var exportFile = '?view=archive&type='+data.exportFormat+'&connkey='+data.connkey;
  var exportFile = data.exportFile;

  $j('#exportProgress').removeClass( 'text-warning' );
  if ( generated ) {
    $j('#downloadLink').text('Download');
    $j('#downloadLink').attr("href", thisUrl + exportFile);
    $j('#exportProgress').addClass( 'text-success' );
    $j('#exportProgress').text(exportSucceededString);
    setTimeout(startDownload, 1500, exportFile);
  } else {
    $j('#exportProgress').addClass( 'text-danger' );
    $j('#exportProgress').text(exportFailedString);
  }
}

function exportEvent() {
  $j.ajax({
    url: thisUrl + '?view=request&request=event&action=download',
    dataType: 'json',
    data: $j('#downloadForm').serialize(),
    success: exportResponse,
    timeout: 0,
    error: function(jqXHR, status, errorThrown) {
      logAjaxFail(jqXHR, status, errorThrown);
      $j('#exportProgress').html('Failed: ' + errorThrown);
    }
  });
  $j('#exportProgress').removeClass('invisible');
}

// Loads the shutdown modal
function getShutdownModal() {
  $j.getJSON(thisUrl + '?request=modal&modal=shutdown')
      .done(function(data) {
        insertModalHtml('shutdownModal', data.html);
        dataOnClickThis();
        $j('#shutdownModal').modal('show');
      })
      .fail(logAjaxFail);
}

function manageShutdownBtns(element) {
  var cmd = element.getAttribute('data-command');
  var when = $j('#when1min').is(':checked') ? '1min' : 'now';
  var respText = $j('#respText');

  $j.getJSON(thisUrl + '?request=shutdown&when=' + when + '&command=' + cmd)
      .done(function(data) {
        respText.removeClass('invisible');
        if ( data.rc ) {
          respText.html('<h2>Error</h2>' + data.output);
        } else {
          $j('#cancelBtn').prop('disabled', false);
          if ( cmd == 'cancel' ) {
            respText.html('<h2>Success</h2>Event has been cancelled');
          } else {
            respText.html('<h2>Success</h2>You may cancel this shutdown by clicking ' + cancelString);
          }
        }
      })
      .fail(logAjaxFail);
}

var thumbnail_timeout;
function thumbnail_onmouseover(event) {
  thumbnail_timeout = setTimeout(function() {
    var img = event.target;
    var imgClass = ( currentView == 'console' ) ? 'zoom-console' : 'zoom';
    var imgAttr = ( currentView == 'frames' ) ? 'full_img_src' : 'stream_src';
    img.src = '';
    img.src = img.getAttribute(imgAttr);
    img.classList.add(imgClass);
  }, 350);
}

function thumbnail_onmouseout(event) {
  clearTimeout(thumbnail_timeout);
  var img = event.target;
  var imgClass = ( currentView == 'console' ) ? 'zoom-console' : 'zoom';
  var imgAttr = ( currentView == 'frames' ) ? 'img_src' : 'still_src';
  img.src = '';
  img.src = img.getAttribute(imgAttr);
  img.classList.remove(imgClass);
}

function initThumbAnimation() {
  if ( ANIMATE_THUMBS ) {
    $j('.colThumbnail img').each(function() {
      this.addEventListener('mouseover', thumbnail_onmouseover, false);
      this.addEventListener('mouseout', thumbnail_onmouseout, false);
    });
  }
}
