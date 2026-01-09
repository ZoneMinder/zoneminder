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

var panZoomEnabled = true; //Add it to settings in the future
var expiredTap; //Time between touch screen clicks. Used to analyze double clicks
var shifted = ctrled = alted = false;
var mainContent = document.getElementById('content');

const showExtruderPanelOnMouseHover = false;
const NAVBAR_RELOAD = document.getElementById('reload'); // Top panel with statistics
const BTN_COLLAPSE = document.getElementById('btn-collapse'); // Button to switch the menu view collapsed/expanded
const SIDEBAR_MAIN = document.getElementById('sidebarMain'); // Left Sidebar with Menu
const SIDEBAR_MAIN_EXTRUDER = document.getElementById('extruderLeft'); // Sliding extruder panel from the left Sidebar

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
  document.querySelectorAll("a[data-on-click-this], button[data-on-click-this], input[data-on-click-this], span[data-on-click-this]").forEach(function attachOnClick(el) {
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
    // List of functions that are allowed to be called via the value of an object's DOM attribute.
    const safeFunc = {
      drawGraph: function() {
        if (typeof drawGraph !== 'undefined' && $j.isFunction(drawGraph)) drawGraph();
      },
      refreshWindow: function() {
        if (typeof refreshWindow !== 'undefined' && $j.isFunction(refreshWindow)) refreshWindow();
      },
      changeScale: function() {
        if (typeof changeScale !== 'undefined' && $j.isFunction(changeScale)) changeScale();
      },
      applyChosen: function() {
        if (typeof applyChosen !== 'undefined' && $j.isFunction(applyChosen)) applyChosen();
      }
    };

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
    $j("#flip").click(navbarTwoFlip);
    $j("#flipNarrow").click(navbarTwoFlip);

    function navbarTwoFlip() {
      $j("#navbar-two").slideToggle("slow");
      const flip = $j("#flip");
      if ( flip.html() == 'keyboard_arrow_up' ) {
        flip.html('keyboard_arrow_down');
        setCookie('zmHeaderFlip', 'down');
      } else {
        flip.html('keyboard_arrow_up');
        setCookie('zmHeaderFlip', 'up');
      }
    }

    // Manage visible object & control button (when pressing a button)
    $j("[data-flip-control-object]").click(function() {
      const _this_ = $j(this);
      const objIconButton = _this_.find("i");
      const obj = $j(_this_.attr('data-flip-control-object'));

      changeButtonIcon(_this_, objIconButton);

      const nameFuncBefore = _this_.attr('data-flip-сontrol-run-before-func') ? _this_.attr('data-flip-сontrol-run-before-func') : null;
      const nameFuncAfter = _this_.attr('data-flip-сontrol-run-after-func') ? _this_.attr('data-flip-сontrol-run-after-func') : null;
      const nameFuncAfterComplet = _this_.attr('data-flip-сontrol-run-after-complet-func') ? _this_.attr('data-flip-сontrol-run-after-complet-func') : null;

      if (nameFuncBefore) {
        $j.each(nameFuncBefore.split(' '), function(i, nameFunc) {
          if (typeof safeFunc[nameFunc] === 'function') safeFunc[nameFunc]();
        });
      }
      if (!_this_.attr('data-on-click-true')) {
        obj.slideToggle("fast", function() {
          if (nameFuncAfterComplet) {
            $j.each(nameFuncAfterComplet.split(' '), function(i, nameFunc) {
              if (typeof safeFunc[nameFunc] === 'function') safeFunc[nameFunc]();
            });
          }
        });
      }
      if (nameFuncAfter) {
        $j.each(nameFuncAfter.split(' '), function(i, nameFunc) {
          if (typeof safeFunc[nameFunc] === 'function') safeFunc[nameFunc]();
        });
      }
    });

    // Manage visible filter bar & control button (after document ready)
    $j("[data-flip-control-object]").each(function() { //let's go through all objects (buttons) and set icons
      const _this_ = $j(this);
      const сookie = getCookie('zmFilterBarFlip'+_this_.attr('data-flip-control-object'));
      const initialStateIcon = _this_.attr('data-initial-state-icon'); //"visible"=Opened block , "hidden"=Closed block or "undefined"=use cookie
      const objIconButton = _this_.find("i");
      const obj = $j(_this_.attr('data-flip-control-object'));

      if (obj.parent().css('display') != 'block') {
        obj.wrap('<div style="display: block"></div>');
      }

      // initialStateIcon takes priority. If there is no cookie, we assume that it is 'visible'
      const stateIcon = (initialStateIcon) ? initialStateIcon : ((сookie == 'hidden') ? 'hidden' : 'visible');
      if (objIconButton.is('[class~="material-icons"]')) { // use material-icons
        if (stateIcon == 'hidden') {
          objIconButton.html(objIconButton.attr('data-icon-hidden'));
          obj.addClass('hidden-shift'); //To prevent jerking when running the "Chosen" script, it is necessary to make the block visible to JS, but invisible to humans!
        } else {
          objIconButton.html(objIconButton.attr('data-icon-visible'));
          obj.removeClass('hidden-shift');
        }
      } else if (objIconButton.is('[class*="fa-"]')) { //use Font Awesome
        if (stateIcon == 'hidden') {
          objIconButton.addClass(objIconButton.attr('data-icon-hidden'));
          obj.addClass('hidden-shift'); //To prevent jerking when running the "Chosen" script, it is necessary to make the block visible to JS, but invisible to humans!
        } else {
          objIconButton.addClass(objIconButton.attr('data-icon-visible'));
          obj.removeClass('hidden-shift');
        }
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

    applyChosen();
  });

  /*
  * params{visibility: null "visible" or "hidden"} - state of the panel before pressing button
  */
  function changeButtonIcon(pressedBtn, target, params) {
    const visibility = (!params) ? null : params.visibility;
    const objIconButton = pressedBtn.find("i");
    const obj = $j(pressedBtn.attr('data-flip-control-object'));
    if ((visibility == "visible") || (obj.is(":visible") && !obj.hasClass("hidden-shift"))) {
      if (objIconButton.is('[class~="material-icons"]')) { // use material-icons
        objIconButton.html(objIconButton.attr('data-icon-hidden'));
      } else if (objIconButton.is('[class*="fa-"]')) { //use Font Awesome
        objIconButton.removeClass(objIconButton.attr('data-icon-visible')).addClass(objIconButton.attr('data-icon-hidden'));
      }
      setCookie('zmFilterBarFlip'+pressedBtn.attr('data-flip-control-object'), 'hidden');
    } else { //hidden
      obj.removeClass('hidden-shift').addClass('hidden'); //It is necessary to make the block invisible both for JS and for humans
      if (objIconButton.is('[class~="material-icons"]')) { // use material-icons
        objIconButton.html(objIconButton.attr('data-icon-visible'));
      } else if (objIconButton.is('[class*="fa-"]')) { //use Font Awesome
        objIconButton.removeClass(objIconButton.attr('data-icon-hidden')).addClass(objIconButton.attr('data-icon-visible'));
      }
      setCookie('zmFilterBarFlip'+pressedBtn.attr('data-flip-control-object'), 'visible');
    }
  }

  // After retieving modal html via Ajax, this will insert it into the DOM
  function insertModalHtml(name, html) {
    let modal = $j('#' + name);

    if (modal.length) {
      modal.replaceWith(html);
    } else {
      $j('body').append(html);
      modal = $j('#' + name);
    }
    return modal;
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
      delete data.auth;
    }
    if (data.auth_relay) {
      auth_relay = data.auth_relay;
      delete data.auth_relay;
    }
    // iterate through all the keys then update each element id with the same name
    for (const key of Object.keys(data)) {
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
  if ( streamObj.responseJSON ) {
    if (streamObj.responseJSON.result == "Error") {
      Error(funcName+' stream error: '+streamObj.responseJSON.message);
      return true;
    }
  } else if ( streamObj.result == "Error" ) {
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

// Called by 'data-on-change=submitThisForm' for Select and in Montage page when date/time changes
function submitThisForm(param = null) {
  var form = this.form;
  var filter = null; // The filter that we previously moved to the left sidebar menu
  if (!form && param && typeof param === 'object' && 'tagName' in param) {
    if (param.tagName == 'FORM') { // A form can be passed as a parameter.
      form = param;
    } else if (param.form) {
      form = param.form;
    }
  }
  if (navbar_type == 'left' && !form) {
    if (currentView == 'console') {
      // We get the form that we process
      form = document.getElementById('monitorFiltersForm');
      // We get a filter
      filter = document.getElementById('fbpanel');
    } else if (currentView == 'montage') {
      form = document.getElementById('monitorFiltersForm');
      // Filter is inside the form.
    } else if (currentView == 'montagereview') {
      form = document.getElementById('montagereview_form');
      filter = document.getElementById('filterMontagereview');
    } else if (currentView == 'watch') {
      form = document.querySelector('#wrapperFilter form');
    }
  }

  if ( ! form ) {
    console.log("No this.form.  element with onchange is not in a form", this, param);
    return;
  }
  if (filter && navbar_type == 'left') {
    // Let's hide the old filter so that it doesn't appear during the transfer...
    filter.style.display = 'none';
    // We return the filter to its place in the form, since in the left side menu the filter should always be inside the form.
    form.prepend(filter);
  }
  if (param && typeof param === 'string') { //ON WATCH PAGE WHEN SELECTING A MONITOR, the object is transferred as PARAM!!!
    var uri = "?" + $j(form).serialize() + param;
    window.location = uri;
  } else {
    form.submit();
  }
}

/**
 * @param {Element} headerCheckbox The select all/none checkbox that was just toggled.
 * @param {DOMString} name The name of the checkboxes to toggle.
 */
function updateFormCheckboxesByName( headerCheckbox ) {
  const name = headerCheckbox.getAttribute("data-checkbox-name");
  const form = headerCheckbox.form;
  const checked = headerCheckbox.checked;
  for (let i = 0, len=form.elements.length; i < len; i++) {
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
  let btn = form.deleteBtn;
  if (!btn) btn = document.getElementById('deleteBtn');
  if (btn) btn.disabled = !checked;
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
  resizeTimer = setTimeout(function() {
    setCookie('zmBrowserSizes', JSON.stringify({
      innerWidth: window.innerWidth,
      innerHeight: window.innerHeight,
      outerWidth: window.outerWidth,
      outerHeight: window.outerHeight,
    }));
    if (typeof changeScale !== 'undefined' && $j.isFunction(changeScale)) {
      //Only for scaleToFit
      changeScale();
    }
    isNavbarOverflown();
  }, 250);
}
window.onresize = endOfResize;

/* scaleToFit
 *
 * Tries to figure out the available space to fit an image into
 * Uses the #content element
 * figures out where bottomEl is in the viewport
 * does calculations
 * scaleEl is the thing to be scaled, should be a jquery object and should have height
 * */
function scaleToFit(baseWidth, baseHeight, scaleEl, bottomEl, container, panZoomScale = 1) {
  //$j(window).on('resize', endOfResize); //set delayed scaling when Scale to Fit is selected
  if (!container) container = $j('#content');
  if (!container) {
    console.error("No container found");
    return;
  }

  const ratio = baseWidth / baseHeight;
  const viewPort = $j(window);
  // jquery does not provide a bottom offset, and offset does not include margins.  outerHeight true minus false gives total vertical margins.
  var bottomLoc = 0;
  if (bottomEl !== false) {
    if (!bottomEl || !bottomEl.length) {
      bottomEl = $j(container[0].lastElementChild);
    }
    bottomLoc = bottomEl.offset().top + (bottomEl.outerHeight(true) - bottomEl.outerHeight()) + bottomEl.outerHeight(true);
    console.log("bottomLoc: " + bottomEl.offset().top + " + (" + bottomEl.outerHeight(true) + ' - ' + bottomEl.outerHeight() +') + '+bottomEl.outerHeight(true) + '='+bottomLoc);
  }
  let newHeight = viewPort.height() - (bottomLoc - scaleEl.outerHeight(true));
  let newWidth = ratio * newHeight;

  if (newHeight < 0 || newWidth > container.width()) {
    // Doesn't fit on screen anyways?
    newWidth = container.width();
    newHeight = newWidth / ratio;
  }
  let autoScale = Math.round(newWidth / baseWidth * SCALE_BASE * panZoomScale);
  // Floor to nearest value % 5. THe 5 is somewhat arbitrary.  The point is that scaling by 88% is not better than 85%. Perhaps it should be to the nearest 10.  Or 25 even.
  autoScale = 5 * Math.floor(autoScale / 5);
  if (autoScale < 10) autoScale = 10;
  console.log(`container.height=${container.height()}, newWidth=${newWidth}, newHeight=${newHeight}, container width=${container.width()}, autoScale=${autoScale}`);
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

function isJSON(str) {
  if (typeof str !== 'string') return false;
  try {
    const result = JSON.parse(str);
    const type = Object.prototype.toString.call(result);
    return type === '[object Object]' || type === '[object Array]'; // We only pass objects and arrays
  } catch (e) {
    return false; // This is also not JSON
  }
};

function setCookie(name, value, seconds) {
  var newValue = (typeof value === 'string' || typeof value === 'boolean') ? value : JSON.stringify(value);
  let expires = "";
  if (seconds) {
    const date = new Date();
    date.setTime(date.getTime() + (seconds*1000));
    expires = "; expires=" + date.toUTCString();
  } else {
    // 2147483647 is 2^31 - 1 which is January of 2038 to avoid the 32bit integer overflow bug.
    expires = "; max-age=2147483647";
  }
  document.cookie = name + "=" + (newValue || "") + expires + "; path=/; samesite=strict";
}

/*
* If JSON is stored in cookies, the function will return an array or object of values.
*/
function getCookie(name) {
  var nameEQ = name + "=";
  var result = null;
  var ca = document.cookie.split(';');
  for (var i=0; i < ca.length; i++) {
    if (result) break;
    var c = ca[i];
    while (c.charAt(0)==' ') c = c.substring(1, c.length);
    if (c.indexOf(nameEQ) == 0) {
      result = c.substring(nameEQ.length, c.length);
      break;
    }
  }
  if (isJSON(result)) result = JSON.parse(result);

  return result;
}

function delCookie(name) {
  document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
}

function bwClickFunction() {
  $j('.bwselect').click(function() {
    var bwval = $j(this).data('pdsa-dropdown-val');
    setCookie("zmBandwidth", bwval);
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
        if (data['result'] != 'Ok') {
          alert('Failed to load logout modal. See javascript console for details.');
          console.log(data);
        } else {
          insertModalHtml('modalLogout', data.html);
          manageModalBtns('modalLogout');
          clickLogout();
        }
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

function strip_html(string) {
  return string.replace(/<[^>]+>/g, '');
}

function logAjaxFail(jqxhr, textStatus, error) {
  if (jqxhr.statusText == 'abort') {
    console.log('request aborted');
    return;
  }
  if (!jqxhr.responseText) {
    console.log("Ajax request failed.  No responseText.  jqxhr follows:\n", jqxhr);
    return;
  }
  console.log("Request Failed: " + textStatus + ", " + error);
  // Icon: Why strip html and whitespace?  We are just debugging it... it might get sent back to be logged in db etc.. but...
  // we might lose a lot of content here.
  //var responseText = strip_html(jqxhr.responseText).trim(); // strip any html or whitespace from the response
  const responseText = jqxhr.responseText;
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

/* Controls the availability of options for selection*/
function manageChannelStream() {
  let select = null;
  let secondPath_ = null;
  if (currentView == 'watch') {
    if (typeof monitorData !== 'undefined') {
      const monitor = monitorData.find((o) => {
        return parseInt(o["id"]) === monitorId;
      });
      if (monitor) {
        secondPath_ = monitor['SecondPath'];
      }
      select = document.querySelector('select[name="streamChannel"]');
    } else {
      console.error("No monitorData in watch view");
    }
  } else if (currentView == 'monitor') {
    // Local source doesn't have second path
    const SecondPathInput = document.querySelector('input[name="newMonitor[SecondPath]"]');
    if (SecondPathInput) {
      secondPath_ = SecondPathInput.value;
    }
    select = document.querySelector('select[name="newMonitor[RTSP2WebStream]"]');
  }
  if (select) {
    select.querySelectorAll("option").forEach(function(el) {
      if (el.value == 'Secondary' && !secondPath_) {
        el.disabled = true;
      } else {
        el.disabled = false;
      }
      applyChosen(select);
    });
  }
}

var thumbnail_timeout;
function thumbnail_onmouseover(event) {
  const img = event.target;
  const imgClass = ( currentView == 'console' ) ? 'zoom-console' : 'zoom';
  const imgAttr = ( currentView == 'frames' ) ? 'full_img_src' : 'stream_src';
  img.src = img.getAttribute(imgAttr);
  if ( currentView == 'console' || currentView == 'monitor' ) {
    const rect = img.getBoundingClientRect();
    const zoomHeight = rect.height * 5; // scale factor defined in css
    if ( rect.bottom + (zoomHeight - rect.height) > window.innerHeight ) {
      img.style.transformOrigin = '0% 100%';
    } else {
      img.style.transformOrigin = '0% 0%';
    }
  }
  thumbnail_timeout = setTimeout(function() {
    img.classList.add(imgClass);
  }, 250);
}

function thumbnail_onmouseout(event) {
  clearTimeout(thumbnail_timeout);
  var img = event.target;
  var imgClass = ( currentView == 'console' ) ? 'zoom-console' : 'zoom';
  var imgAttr = ( currentView == 'frames' ) ? 'img_src' : 'still_src';
  img.src = img.getAttribute(imgAttr);
  img.classList.remove(imgClass);
  if ( currentView == 'console' || currentView == 'monitor' ) {
    img.style.transformOrigin = '';
  }
}

function initThumbAnimation() {
  if ( ANIMATE_THUMBS ) {
    $j('.colThumbnail img').each(function() {
      this.addEventListener('mouseover', thumbnail_onmouseover, false);
      this.addEventListener('mouseout', thumbnail_onmouseout, false);
    });
  }
}

/* View in fullscreen */
function openFullscreen(elem) {
  if (elem.requestFullscreen) {
    elem.requestFullscreen();
  } else if (elem.webkitRequestFullscreen) {
    /* Safari */
    elem.webkitRequestFullscreen();
  } else if (elem.msRequestFullscreen) {
    /* IE11 */
    elem.msRequestFullscreen();
  }
}

/* Close fullscreen */
function closeFullscreen() {
  if (document.exitFullscreen) {
    document.exitFullscreen();
  } else if (document.webkitExitFullscreen) {
    /* Safari */
    document.webkitExitFullscreen();
  } else if (document.msExitFullscreen) {
    /* IE11 */
    document.msExitFullscreen();
  }
}

function toggle_password_visibility(element) {
  const input = document.getElementById(element.getAttribute('data-password-input'));
  if (!input) {
    console.log("Input not found! " + element.getAttribute('data-password-input'));
    return;
  }
  if (element.innerHTML=='visibility') {
    input.type = 'text';
    element.innerHTML = 'visibility_off';
  } else {
    input.type = 'password';
    element.innerHTML='visibility';
  }
}

/**
 * sends a request to the specified url from a form. this will change the window location.
 * @param {string} path the path to send the post request to
 * @param {object} params the parameters to add to the url
 * @param {string} [method=post] the method to use on the form
 */

function post(path, params, method='post') {
  // The rest of this code assumes you are not using a library.
  // It can be made less verbose if you use one.
  const form = document.createElement('form');
  form.method = method;
  form.action = path;
  if (ZM_ENABLE_CSRF_MAGIC === '1') {
    const csrfField = document.createElement('input');
    csrfField.type = 'hidden';
    csrfField.name = csrfMagicName;
    csrfField.value = csrfMagicToken;
    form.appendChild(csrfField);
  }

  for (const key in params) {
    if (params.hasOwnProperty(key)) {
      if (Array.isArray(params[key])) {
        for (let i=0, len=params[key].length; i<len; i++) {
          const hiddenField = document.createElement('input');
          hiddenField.type = 'hidden';
          hiddenField.name = key;
          hiddenField.value = params[key][i];
          form.appendChild(hiddenField);
        }
      } else {
        const hiddenField = document.createElement('input');
        hiddenField.type = 'hidden';
        hiddenField.name = key;
        hiddenField.value = params[key];
        form.appendChild(hiddenField);
      }
    } // end if hasOwnProperty(key)
  }
  document.body.appendChild(form);
  form.submit();
}

function isMobile() {
  var result = false;
  // device detection
  if (/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|ipad|iris|kindle|Android|Silk|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(navigator.userAgent) || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(navigator.userAgent.substring(0, 4))) {
    result = true;
  }
  return result;
}

function isTouchDevice() {
  return !!(('ontouchstart' in window) || window.DocumentTouch && document instanceof window.DocumentTouch);
}

function destroyChosen(selector = '') {
  if (typeof selector === 'string') {
    $j(selector + '.chosen').chosen('destroy');
  } else {
    if ($j(selector).hasClass('chosen')) {
      $j(selector).chosen('destroy');
    }
  }
}

function applyChosen(selector = '') {
  const limit_search_threshold = 10;
  var [obj_1, obj_2, obj_3] = '';
  destroyChosen(selector);
  if (typeof selector === 'string') {
    obj_1 = $j(selector + '.chosen').not('.hidden, .hidden-shift, .chosen-full-width, .chosen-auto-width');
    obj_2 = $j(selector + '.chosen.chosen-full-width').not('.hidden, .hidden-shift');
    obj_3 = $j(selector + '.chosen.chosen-auto-width').not('.hidden, .hidden-shift');
  } else {
    if (!$j(selector).hasClass('chosen')) return;
    obj_1 = $j(selector).not('.hidden, .hidden-shift, .chosen-full-width, .chosen-auto-width');
    obj_2 = $j(selector).not('.hidden, .hidden-shift').hasClass('chosen-full-width') ? $j(selector) : '';
    obj_3 = $j(selector).not('.hidden, .hidden-shift').hasClass('chosen-auto-width') ? $j(selector) : '';
  }
  if (obj_1) {
    obj_1.chosen({allow_single_deselect: true, disable_search_threshold: limit_search_threshold, search_contains: true});
  }
  if (obj_2) {
    obj_2.chosen({allow_single_deselect: true, disable_search_threshold: limit_search_threshold, search_contains: true, width: "100%"});
  }
  if (obj_3) {
    obj_3.chosen({allow_single_deselect: true, disable_search_threshold: limit_search_threshold, search_contains: true, width: "auto"});
  }
}

function stringToNumber(str) {
  return parseInt(String(str).replace(/\D/g, ''));
}

function thisClickOnStreamObject(clickObj) {
  if (clickObj.id) {
    if (clickObj.id.indexOf('evtStream') != -1 || clickObj.id.indexOf('liveStream') != -1) {
      return true;
    } else if (clickObj.id.indexOf('monitorStatus') != -1) {
      return document.getElementById('monitor'+stringToNumber(clickObj.id));
    } else if (clickObj.id.indexOf('videoobj') != -1) {
      return document.getElementById('eventVideo');
    } else return false;
  } else {
    // When using go2rtc there will be a <video> element with no ID wrapped in a <video-stream> with an ID of !
    if (clickObj.closest('video-stream')) return true;
  };
  return false;
}

/* For mobile device Not implemented yet. */
function thisClickOnTimeline(clickObj) {
  return false;
}

var doubleTouchExecute = function(event, touchEvent) {
//  if (touchEvent.target.id &&
//    (touchEvent.target.id.indexOf('evtStream') != -1 || touchEvent.target.id.indexOf('liveStream') != -1 || touchEvent.target.id.indexOf('monitorStatus') != -1)) {
  if (thisClickOnStreamObject(touchEvent.target)) {
    doubleClickOnStream(event, touchEvent);
  } else if (thisClickOnTimeline(touchEvent.target)) {
    doubleTouchOnTimeline(event, touchEvent);
  }
};

var doubleClickOnStream = function(event, touchEvent) {
  if (shifted || ctrled || alted) {
    console.log("Shift or Ctrl or Alt button was pressed, double-click event was not processed.");
    return;
  }
  let target = null;
  if (event.target) {// Click NOT on touch screen, use THIS
    //Process only double clicks directly on the image, excluding clicks,
    //for example, on zoom buttons and other elements located in the image area.
    const fullScreenObject = thisClickOnStreamObject(event.target);
    if (fullScreenObject === true) {
      target = this;
    } else if (fullScreenObject !== false) {
      target = fullScreenObject;
    }
  } else {// Click on touch screen, use EVENT
    //if (touchEvent.target.id &&
    //  (touchEvent.target.id.indexOf('evtStream') != -1 || touchEvent.target.id.indexOf('liveStream') != -1)) {
    target = event;
    //}
  }

  if (target) {
    if (document.fullscreenElement) {
      if (getCookie('zmEventStats') && typeof eventStats !== "undefined") {
        //Event page
        eventStats.toggle(true);
        wrapperEventVideo.removeClass('col-sm-12').addClass('col-sm-8');
        changeScale();
      } else if (getCookie('zmCycleShow') && typeof sidebarView !== "undefined") {
        //Watch page
        sidebarView.toggle(true);
        monitorsSetScale(monitorId);
      }
      closeFullscreen();
    } else {
      if (getCookie('zmEventStats') && typeof eventStats !== "undefined") {
        //Event page
        eventStats.toggle(false);
        wrapperEventVideo.removeClass('col-sm-8').addClass('col-sm-12');
        changeScale();
      } else if (getCookie('zmCycleShow') && typeof sidebarView !== "undefined") {
        //Watch page
        sidebarView.toggle(false);
        monitorsSetScale(monitorId);
      }
      openFullscreen(target);
    }
    if (isMobile()) {
      setTimeout(function() {
        //For some mobile devices resizing does not work. You need to set a delay and re-call the 'resize' event
        window.dispatchEvent(new Event('resize'));
      }, 500);
    }
  }
};

var doubleTouch = function(e) {
  if (e.touches.length === 1) {
    if (!expiredTap) {
      expiredTap = e.timeStamp + 300;
    } else if (e.timeStamp <= expiredTap) {
      // remove the default of this event ( Zoom )
      e.preventDefault();
      //doubleClickOnStream(this, e);
      doubleTouchExecute(this, e);
      // then reset the variable for other "double Touches" event
      expiredTap = null;
    } else {
      // if the second touch was expired, make it as it's the first
      expiredTap = e.timeStamp + 300;
    }
  }
};

function setButtonSizeOnStream() {
  const elStream = document.querySelectorAll('[id ^= "liveStream"], [id ^= "evtStream"], [id = "videoobj"]');
  Array.prototype.forEach.call(elStream, (el) => {
    //It is necessary to calculate the size for each Stream, because on the Montage page they can be of different sizes.
    const w = el.offsetWidth;
    // #videoFeedStream - on Event page
    const monitorId = (stringToNumber(el.id)) ? stringToNumber(el.id) : stringToNumber(el.closest('[id ^= "videoFeedStream"]').id);
    const buttonsBlock = document.getElementById('button_zoom' + monitorId);
    if (!buttonsBlock) return;
    const buttons = buttonsBlock.querySelectorAll(`
      button.btn.btn-zoom-out span,
      button.btn.btn-zoom-in span,
      button.btn.btn-view-watch span,
      button.btn.btn-fullscreen span,
      button.btn.btn-edit-monitor span`
    );
    Array.prototype.forEach.call(buttons, (btn) => {
      const btnWeight = (w/10 < 100) ? w/10 : 100;
      btn.style.fontSize = btnWeight + "px";
      btn.style.margin = -btnWeight/20 + "px";
    });
  });
}

/*
* date - object type Date()
* shift.offset - number (can be negative)
* shift.period - (Date, Month, Day, Hour, Minute, Sec, MilliSec)
* highPrecision - accuracy up to thousandths of a second
*/
function dateTimeToISOLocal(date, shift={}, highPrecision = false) {
  var d = date;
  if (shift.offset && shift.period) {
    if (shift.period == 'Date') {
      d = new Date(date.setDate(date.getDate() + shift.offset)); //Day
    } else if (shift.period == 'Month') {
      d = new Date(date.setMonth(date.getMonth() + shift.offset)); //Month
    } else if (shift.period == 'Day') {
      d = new Date(date.setHours(date.getHours() + shift.offset*24)); //24 hours
    } else if (shift.period == 'Hour') {
      d = new Date(date.setHours(date.getHours() + shift.offset)); //Hour
    } else if (shift.period == 'Minute') {
      d = new Date(date.setMinutes(date.getMinutes() + shift.offset)); //Minute
    } else if (shift.period == 'Sec') {
      d = new Date(date.setSeconds(date.getSeconds() + shift.offset)); //Second
    } else if (shift.period == 'MilliSec') {
      d = new Date(date.setMilliseconds(date.getMilliseconds() + shift.offset)); //Millisecond
    }
  }

  //const z = n => ('0' + n).slice(-2);
  //let off = d.getTimezoneOffset();
  //const sign = off < 0 ? '+' : '-';
  //off = Math.abs(off);
  if (highPrecision) {
    return new Date(d.getTime() - (d.getTimezoneOffset() * 60000))
        .toISOString();
  } else {
    return new Date(d.getTime() - (d.getTimezoneOffset() * 60000))
        .toISOString()
        //.slice(0, -1) + sign + z(off / 60 | 0) + ':' + z(off % 60);
        .slice(0, -1)
        .split('.')[0].replace(/[T]/g, ' '); //Transformation from "2024-06-20T15:12:13.145" to "2024-06-20 15:12:13"
  }
}

function canPlayCodec(filename) {
  const re = /\.(\w+)\.(\w+)$/i;
  const matches = re.exec(filename);
  if (matches && matches.length) {
    const video = document.createElement('video');
    if (matches[1] == 'av1') matches[1] = 'av01';
    else if (matches[1] == 'h264') matches[1] = 'avc1';
    else if (matches[1] == 'hevc') matches[1] = 'hvc1';
    else {
      console.log('matches didnt match'+matches[1]);
    }
    video.muted = true;

    const can = video.canPlayType('video/mp4; codecs="'+matches[1]+'"');
    if (can == "probably") {
      console.log("can probably play "+matches[1]);
      return true;
    } else if (can == "maybe") {
      console.log("can maybe play "+matches[1]);
      return true;
    }
    console.log("cannot play "+matches[1]);
    return false;
  } else {
    console.log("Failed to match re on ", filename);
  }
  return false;
}

/*
* Top bar with statistics
* This function is necessary because
* .dropdown-menu cannot extend beyond #reload container if #reload overflow == auto
* overflow == auto for #reload is needed to scroll #reload on narrow screen.
* Now on a wide screen we have a beautiful .dropdown-menu, on a narrow screen it is less beautiful.
*/
function isNavbarOverflown() {
  if (!NAVBAR_RELOAD) return; // For example, when you log in, nothing will happen...
  // If it doesn't fit, turn on overflow
  if (NAVBAR_RELOAD.scrollWidth > NAVBAR_RELOAD.clientWidth) {
    NAVBAR_RELOAD.style.overflowX = 'auto';
    document.querySelectorAll("#panel .dropdown-menu").forEach(function(el) {
      el.classList.add('overflown');
    });
  } else {
    NAVBAR_RELOAD.style.overflowX = 'visible';
    document.querySelectorAll("#panel .dropdown-menu").forEach(function(el) {
      el.classList.remove('overflown');
    });
  }
}

function changeAttrTitle(collapsed = null) {
  if (!SIDEBAR_MAIN) return;
  if (collapsed === null) collapsed = SIDEBAR_MAIN.classList.contains('collapsed');
  // First level menu
  // Let's create (for collapsed, to make it clearer or if the title doesn't fit) or remove (for expanded, so as not to irritate) the "Title" attribute
  SIDEBAR_MAIN.querySelectorAll("nav>ul>li.menu-item>a").forEach(function(el) {
    const titleEl = el.querySelector("span.menu-title");
    if (collapsed || titleEl.scrollWidth > titleEl.clientWidth) {
      el.setAttribute('title', el.querySelector("span.menu-title").textContent);
    } else {
      el.setAttribute('title', '');
    }
  });

  // For submenu we will add only if the title does not fit in the visible area in width...
  SIDEBAR_MAIN.querySelectorAll("nav>ul>li.menu-item.sub-menu .sub-menu-list>ul>li.menu-item a").forEach(function(el) {
    const titleEl = el.querySelector("span");
    if (titleEl.scrollWidth > titleEl.clientWidth) {
      el.setAttribute('title', el.querySelector("span.menu-title").textContent);
    } else {
      el.setAttribute('title', '');
    }
  });
}

/* We create a retractable extruder block with filter settings (we move filters from the top panel) and a button in the left Sidebar menu */
function insertControlModuleMenu() {
  var filter = null;
  if (currentView == 'console') {
    destroyChosen(); // It is required to be performed BEFORE receiving the object and only for those pages on which we transfer the filter
    filter = document.querySelector('#fbpanel');
  } else if (currentView == 'montage') {
    destroyChosen();
    filter = document.querySelector('#monitorFiltersForm');
  } else if (currentView == 'montagereview') {
    destroyChosen();
    filter = document.createElement('div');
    filter.setAttribute("id", "filterMontagereview");

    const filterOne = document.querySelector('#mfbpanel .controlHeader');
    const filterTwo = document.querySelector('#fieldsTable');
    filter.prepend(filterTwo);
    filter.prepend(filterOne);
  } else if (currentView == 'watch') {
    destroyChosen();
    filter = document.querySelector('.controlHeader form');
  } else if (currentView == 'report_event_audit') {
    destroyChosen();
    filter = document.querySelector('#content form');
  } else if (currentView == 'events') {
    destroyChosen();
    filter = document.querySelector('#fieldsTable');
    // We change the layout to remove the resulting void.
    const toolbar = document.querySelector('#toolbar');
    // The first div that is "col-sm-1"
    const divFirst = toolbar.querySelector('.col-sm-1');
    if (divFirst) {
      divFirst.classList.remove('col-sm-1');
      divFirst.classList.add('col-sm-4');
    }
    // The second div, which is "col-sm-9"
    const divSecond = toolbar.querySelector('.col-sm-9');
    if (divSecond) {
      divSecond.style.display = 'none';
    }
    // The third div, which is "col-sm-2"
    const divThird = toolbar.querySelector('.col-sm-2');
    if (divThird) {
      divThird.classList.remove('col-sm-2');
      divThird.classList.add('col-sm-8');
    }
  }

  if (!filter) return;
  filter.classList.add('filter-block');

  // Restore visibility. If the block was hidden
  filter.classList.remove('hidden-shift');
  filter.style.display = '';

  //Create a button to open/hide the filter settings control
  var el = document.createElement('div');
  el.setAttribute("class", "menu-item");
  el.innerHTML = `
    <a href="#" class="text-success" title="Filter settings">
      <span class="bg-light menu-icon"><i class="material-icons">tune</i></span>
      <span class="menu-title">Filter settings</span>
    </a>
  `;

  const menuControlModule = $j(document.querySelector('#menuControlModule'));
  const containerExtruderLeft = $j(document.querySelector('#contextExtruderLeft'));
  menuControlModule.prepend(el); // Menu line

  // Let's create a wrapper for the filter, since the filter can start with a <span> tag or something similar.
  var wrapper = document.createElement('div');
  wrapper.setAttribute("id", "wrapperFilter");
  // Let's add a filter to wrapper
  wrapper.prepend(filter);
  // We will create a button to close extruder panel, which is relevant for narrow screens
  var btnClose = document.createElement('span');
  btnClose.setAttribute("class", "btn");
  btnClose.setAttribute("id", "wrapperBtnCloseExtruder");
  //btnClose.style.position = "absolute";
  //btnClose.style.top = "0";
  //btnClose.style.right = "0";
  btnClose.innerHTML = `<i id="btnCloseExtruder" class="material-icons">close</i>`;
  wrapper.prepend(btnClose);
  // Let's place the filter itself
  containerExtruderLeft.replaceWith(wrapper);

  $j(SIDEBAR_MAIN_EXTRUDER).buildMbExtruder({
    attachToParentSide: "right", // Bind to right side of parent
    bindToButtonByHeight: document.querySelector('#menuControlModule'), // Bind vertical position to button. Relevant when the button moves vertically, so that the extruder follows it.
    position: "left",
    selectorResponsiveBlock: '.filter-block',
    extruderParentElement: document.getElementById('sidebarMain'),
    noFlap: true,
    width: 400,
    topBlock: 300,
    topFlap: 100,
    extruderOpacity: .9,
    //zIndex: 100,
    onExtOpen: function() {},
    onExtContentLoad: function() {},
    onExtClose: function() {}
  });

  // Assign to Show/hide button
  menuControlModule.on("click", function() {
    if (!SIDEBAR_MAIN_EXTRUDER.classList.contains('isOpened')) {
      $j(SIDEBAR_MAIN_EXTRUDER).openMbExtruder();
    } else {
      $j(SIDEBAR_MAIN_EXTRUDER).closeMbExtruder();
    }
  });

  // NECESSARY BECAUSE DOM HAS BEEN REBUILDED!
  dataOnClickThis();
  dataOnClick();
  dataOnClickTrue();
  dataOnChangeThis();
  dataOnChange();
  dataOnInput();
  dataOnInputThis();
  applyChosen();
  initDatepicker();
  if (getCookie('zmVisibleMbExtruder')) {
    // The extruder panel was open when the previous page was closed. Perhaps the filter settings were changed before the previous page was closed.
    $j(SIDEBAR_MAIN_EXTRUDER).openMbExtruder();
  }

  /* Need to change overflow for extruder sidebar
  * If the "chosen" drop-down list fits vertically into the browser window, then overflow = 'visible' is for beauty.
  * That is, the list will be on top of the block, and the block will not increase in height. Otherwise, we will set overflow = 'auto' for the panel so that the "chosen" drop-down list is fully accessible.
  * We listen to ".chosen-container" for ".chosen-container-active" to be added or removed.
  */
  document.querySelectorAll(".chosen-container").forEach(function(el) {
    const ob = new MutationObserver(function() {
      const parent = SIDEBAR_MAIN_EXTRUDER.querySelector('.filter-block');
      if (!parent) return; // Page closing moment
      if (el.classList.contains("chosen-container-active")) {
        const chosenDropBlock = el.querySelector('.chosen-drop');
        //const [leftDropBlock, topDropBlock] = findPos(chosenDropBlock); // Eslint complains about not using leftDropBlock
        const topDropBlock = findPos(chosenDropBlock)[1];

        if (chosenDropBlock.clientHeight + topDropBlock > window.innerHeight ) { // There is not enough space at the bottom
          parent.style.overflow = 'auto';
        } else {
          parent.style.overflow = 'visible';
        }
      } else {
        //parent.style.overflow = 'visible';
      }
    });
    ob.observe(el, {
      attributes: true,
      attributeFilter: ["class"]
    });
  });
}

// Get the position of an element. Relevant for nested position == fixed&absolute
function findPos(obj, foundScrollLeft, foundScrollTop) {
  var curleft = 0;
  var curtop = 0;
  if (obj.offsetLeft) curleft += parseInt(obj.offsetLeft);
  if (obj.offsetTop) curtop += parseInt(obj.offsetTop);
  if (obj.scrollTop && obj.scrollTop > 0) {
    curtop -= parseInt(obj.scrollTop);
    foundScrollTop = true;
  }
  if (obj.scrollLeft && obj.scrollLeft > 0) {
    curleft -= parseInt(obj.scrollLeft);
    foundScrollLeft = true;
  }
  if (obj.offsetParent) {
    var pos = findPos(obj.offsetParent, foundScrollLeft, foundScrollTop);
    curleft += pos[0];
    curtop += pos[1];
  } else if (obj.ownerDocument) {
    var thewindow = obj.ownerDocument.defaultView;
    if (!thewindow && obj.ownerDocument.parentWindow) thewindow = obj.ownerDocument.parentWindow;
    if (thewindow) {
      if (!foundScrollTop && thewindow.scrollY && thewindow.scrollY > 0) curtop -= parseInt(thewindow.scrollY);
      if (!foundScrollLeft && thewindow.scrollX && thewindow.scrollX > 0) curleft -= parseInt(thewindow.scrollX);
      if (thewindow.frameElement) {
        var pos = findPos(thewindow.frameElement);
        curleft += pos[0];
        curtop += pos[1];
      }
    }
  }

  return [curleft, curtop];
}

/* Handling <input> change */
function handleChangeInputTag(evt) {
  // Managing availability of channel stream selection
  manageChannelStream();
}

/* Handling a mouse click */
function handleClickGeneral(evt) {
  const target = evt.target;
  if (navbar_type == 'left') {
    if (SIDEBAR_MAIN_EXTRUDER.contains(target)) {
      // Click on any element inside the extruder panel from the Sidebar
    } else {
      // Click outside the extruder panel
      if (!SIDEBAR_MAIN.contains(target) &&
          //"datepicker" changes the DOM very quickly when the month changes. Closest does not have time to go through all the parents, but only manages to get to the first parent. It is necessary to additionally analyze '.ui-datepicker-prev' and '.ui-datepicker-next'
          !target.closest('.ui-datepicker') &&
          !target.closest('.ui-datepicker-prev') &&
          !target.closest('.ui-datepicker-next') &&
          !target.matches('button[name="deleteBtn"]')) { // Multi select clear button
        // Click outside the extruder panel Sidebar (except clicking on "datepicker"). Close the extruder panel
        closeMbExtruder();
      }
    }
    // Collapse or expand the menu.
    if (BTN_COLLAPSE && BTN_COLLAPSE.contains(target)) {
      setTimeout(function() {
        // Call only after finishing expanding or collapsing. Otherwise the dimensions will be incorrect.
        const collapsed = SIDEBAR_MAIN.classList.contains('collapsed');
        setCookie('zmSidebarMainCollapse', collapsed);
        changeAttrTitle(collapsed);
      }, 500);
    }
    // Extruder panel close button with filters. Appears on narrow screens
    if ('btnCloseExtruder' == target.id && SIDEBAR_MAIN_EXTRUDER) {
      closeMbExtruder();
    }
  }

  // Click on <input> to open the "datepicker" window
  // For ui-datepicker we track the change of style "display" and to front
  // This is necessary because some blocks, such as "extruder" always push themselves to the foreground. At the same time, "datepicker" should ALWAYS be displayed on top of all blocks. "z-index: XXX !important will not help in this case!
  if (target.classList.contains('hasDatepicker')) {
    const datepicker = document.getElementById('ui-datepicker-div');
    if (datepicker) {
      datepicker.style.removeProperty('z-index'); // Without this, problems are possible.
      datepicker.style.setProperty('z-index', '1000000', 'important'); //Now "datepicker" will always be displayed on top of other blocks.
    }
  }
}

/* Handle any action on the touch screen */
function handleTouchActionGeneral(action, evt) {
  //https://developer.mozilla.org/en-US/docs/Web/API/Touch_events
  if (action == 'touchstart') {
    managePanZoomButton(evt);
  } else if (action == 'touchend') {
  } else if (action == 'touchcancel') {
  } else if (action == 'touchmove') {
    //evt.preventDefault();
  }
}

/* Processing any key press*/
function handleKeydownGeneral(evt) {
  const target = evt.target;
  const key = evt.key;
  // Controls pressing "Enter" inside the sliding panel from Sidebar. Used to submit the form to the Console page.
  if (navbar_type == 'left' && key == 'Enter') {
    if (SIDEBAR_MAIN_EXTRUDER.contains(target)) {
      if (target.getAttribute('data-on-change')) {
        return;
      } else {
        const chosenContainer = target.closest('.chosen-container');
        if (chosenContainer && chosenContainer.previousElementSibling.getAttribute('data-on-change') == 'submitThisForm') {
          return;
        }
      }
      submitThisForm();
    }
  }
}

function handleMouseover(evt) {
  manageVisibilityVideoPlayerControlPanel(evt, 'show');

  if (navbar_type == 'left') {
    manageVisibilitySidebarExtruderPanel(evt, 'show');
  }
}

function handleMouseout(evt) {
  manageVisibilityVideoPlayerControlPanel(evt, 'hide');
  if (navbar_type == 'left') {
    manageVisibilitySidebarExtruderPanel(evt, 'hide');
  }
}

function manageVisibilitySidebarExtruderPanel(evt, action) {
  if (!showExtruderPanelOnMouseHover) return;
  if (evt.target.closest('#menuControlModule') &&
  // Avoid false positives when moving mouse inside '#menuControlModule'
      (evt.relatedTarget && evt.relatedTarget.closest('#menuControlModule') != evt.target.closest('#menuControlModule'))) {
    if (action == 'show') {
      $j(SIDEBAR_MAIN_EXTRUDER).openMbExtruder();
    } else if (action == 'hide') {
      // We don't do anything yet, because now the block closes either when you click on the button or anywhere on the page
    }
  }
}

function manageVisibilityVideoPlayerControlPanel(evt, action) {
  if (thisClickOnStreamObject(evt.target)) {
    let video = evt.target.querySelector('video');
    if (!video) {
      video = evt.target.tagName == 'VIDEO' ? evt.target : null;
    }
    if (!video) {
      video = evt.target.getAttribute('tagName');
    }
    if (video && !video.closest('#videoobj')) {
      // We do not touch the video.js object, since it has its own controls.
      if (action == 'hide') {
        video.removeAttribute('controls');
      } else if (action == 'show') {
        video.setAttribute('controls', '');
      }
    }
  }
}

function initDatepicker() {
  if (currentView == 'events') {
    initDatepickerEventsPage();
  } else if (currentView == 'montagereview') {
    initDatepickerMontageReviewPage();
  } else if (currentView == 'report_event_audit') {
    initDatepickerReportEventAuditPage();
  }

  // When hiding the "datepicker" you need to clear the 'z-index' property so that it does not affect other elements
  document.querySelectorAll(".ui-datepicker").forEach(function(el) {
    const ob = new MutationObserver(function(mutations) {
      mutations.forEach(function(mutation) {
        if (mutation.attributeName === 'style' && mutation.target.style.display === 'none' && mutation.target.style.zIndex) {
          console.log(">>zIndex>", mutation.target.style.zIndex);
          mutation.target.style.removeProperty('z-index');
        }
      });
    });
    ob.observe(el, {
      attributes: true,
      attributeFilter: ["style"]
    });
  });
}

function managePanZoomButton(evt) {
  var url = null;
  if (panZoomEnabled) {
    const targetId = evt.target.id;
    var monitorId_ = null; // Resolve variable conflict. ToDo: In general, you need to use objects.
    if (!evt.target.closest('.imageFeed') && !(evt.target.closest('#videoFeed'))) {
      // Click was outside '.imageFeed'
      $j('[id^="button_zoom"]').addClass('hidden');
      return;
    } else {
      $j('#button_zoom' + stringToNumber(targetId)).removeClass('hidden');
    }
    if (!('getAttribute' in evt.currentTarget)) return; // Touchscreen tap on '.imageFeed' does not have 'currentTarget'
    //evt.preventDefault();
    // We are looking for an object with an ID, because there may be another element in the button.
    const obj = targetId ? evt.target : evt.target.parentElement;
    if (!obj) {
      console.log("No obj found", targetId, evt.target, evt.target.parentElement);
      return;
    }

    if (currentView == 'watch') {
      monitorId_ = monitorId;
    } else if (currentView == 'montage') {
      // On Montage page with mode==EDITING it is forbidden to use PanZoom
      if (mode == EDITING) return;
      monitorId_ = evt.currentTarget.getAttribute("data-monitor-id");
    } else if (currentView == 'event') {
      monitorId_ = eventData.MonitorId;
    }

    if (obj.className.includes('btn-view-watch')) {
      url = '?view=watch&mid='+monitorId_;
    } else if (obj.className.includes('btn-edit-monitor')) {
      url = '?view=monitor&mid='+monitorId_;
    } else if (obj.className.includes('btn-fullscreen')) {
      if (document.fullscreenElement) {
        closeFullscreen();
      } else {
        openFullscreen(document.getElementById('monitor'+evt.currentTarget.getAttribute("data-monitor-id")));
      }
    }
    if (url) {
      if (evt.ctrlKey) {
        window.open(url, '_blank');
      } else {
        window.location.assign(url);
      }
    }
    // Zoom by mouse click
    if (thisClickOnStreamObject(obj)) {
      zmPanZoom.click(monitorId_);
    }
  }
}

function closeMbExtruder(updateCookie = false) {
  if (!SIDEBAR_MAIN_EXTRUDER) return;
  if (updateCookie) {
    // We will save the panel visibility in cookies so that we can restore the state after opening a new page.
    // If the panel was open, it means that the filter was most likely adjusted.
    // It is probably worth analyzing whether a new page will be opened or the old one but with different parameters.
    setCookie('zmVisibleMbExtruder', !!SIDEBAR_MAIN_EXTRUDER.classList.contains('isOpened'));
  }
  $j(SIDEBAR_MAIN_EXTRUDER).closeMbExtruder();
}

/*
* The call is configured in \www\skins\classic\views\_monitor_filters.php
* If many options were selected in the filter, then deleting them one by one takes a long time; it is easier to delete everything with one button.
*/
function resetSelectElement(el) {
  console.log("clearSelectMultiply_el=>", el);
  const selectElement = document.querySelector('select[name="'+el.getAttribute('data-select-target')+'"]');
  if (!selectElement) return;
  Array.from(selectElement.options).forEach((option) => {
    option.selected = false;
  });
  applyChosen(selectElement);

  if (currentView == 'events') {
    filterEvents(clickedElement = selectElement);
  } else if (currentView == 'console') {
    monitorFilterOnChange();
  } else {
    submitThisForm(this.closest('form'));
  }
}

function initPageGeneral() {
  $j(document).on('keyup.global keydown.global', function handleKey(e) {
    shifted = e.shiftKey ? e.shiftKey : e.shift;
    ctrled = e.ctrlKey;
    alted = e.altKey;
  });

  if (navbar_type == 'left') {
    if ((!isTouchDevice() || !isMobile()) && NAVBAR_RELOAD) {
      // Increase the width of the scrollbar for NON-mobile or NON-touch devices
      NAVBAR_RELOAD.classList.add('high-scroll-bar');
    }
    changeAttrTitle();
    setTimeout(function() {
      // It takes time for all DOM actions to complete, especially with regard to block visibility.
      insertControlModuleMenu();
      isNavbarOverflown();
    }, 200);

    // Swipe support, configurable via "data-swipe" in \skins\classic\includes\functions.php
    document.addEventListener('swiped', function addListenerGlobalSwiped(e) {
      //console.log(e.detail); // see event data below
      if (e.detail.dir == 'right') {
        if (e.detail.xStart < 80) {
          // SideBar Management
          if (SIDEBAR_MAIN.classList.contains('toggled')) { // The menu is already visible, but you can expand it wider
            SIDEBAR_MAIN.classList.remove('collapsed');
          } else {
            SIDEBAR_MAIN.classList.add('toggled');
          }
        }
      } else if (e.detail.dir == 'left') {
        if ((e.detail.xStart < 80) || ((e.detail.xStart < 250) && (!SIDEBAR_MAIN.classList.contains('collapsed') && SIDEBAR_MAIN.classList.contains('toggled')))) {
          // SideBar Management
          if (!SIDEBAR_MAIN.classList.contains('collapsed') && SIDEBAR_MAIN.classList.contains('toggled')) { // Fully expanded
            SIDEBAR_MAIN.classList.add('collapsed');
          } else {
            SIDEBAR_MAIN.classList.remove('toggled');
          }
        }
      } else if (e.detail.dir == 'down') {
      } else if (e.detail.dir == 'up') {
      }
    });
  }

  var observerNavbarReload = new window.ResizeObserver((entries) => {
    endOfResize();
  });
  if (NAVBAR_RELOAD) {
    observerNavbarReload.observe(NAVBAR_RELOAD);
  }

  if (['montage', 'watch', 'devices', 'reports', 'monitorpreset', 'monitorprobe', 'onvifprobe', 'timeline'].includes(currentView)) {
    mainContent = document.getElementById('page');
  } else if (currentView == 'options') {
    mainContent = document.getElementById('optionsContainer');
  }
  var mainContentJ = $j(mainContent);

  /* Assigning global handlers!
  ** IMPORTANT! It will not be possible to remove assigned handlers using the removeEventListener method, since the functions are anonymous
  */
  document.body.addEventListener('input', function addListenerGlobalInputTag(event) {
    handleChangeInputTag(event);
  });
  document.body.addEventListener('click', function addListenerGlobalClick(event) {
    handleClickGeneral(event);
  });
  document.body.addEventListener('keydown', function addListenerGlobalKeydown(event) {
    handleKeydownGeneral(event);
  });

  document.body.addEventListener('mouseover', function addListenerGlobalMouseover(event) {
    handleMouseover(event);
  });
  document.body.addEventListener('mouseout', function addListenerGlobalMouseout(event) {
    handleMouseout(event);
  });

  // Support for touch devices.
  ['touchstart', 'touchend', 'touchcancel', 'touchmove'].forEach(function(action) {
    document.addEventListener(action, function addListenerGlobalTouchAction(event) {
      handleTouchActionGeneral(action, event);
    }, {passive: false}); // false - to avoid an error "Unable to preventDefault inside passive event listener due to target being treated as passive."
  });

  // Remove the 'controls' attribute in all 'video' tags to be controlled using 'manageVisibilityVideoPlayerControlPanel'
  setTimeout(function() {
    // Delay required for DOM rendering
    document.querySelectorAll("video").forEach(function removeControlsAttributeFromVideoTags(el) {
      el.removeAttribute('controls');
    });
  }, 200);

  // https://web.dev/articles/bfcache Firefox has a peculiar behavior of caching the previous page.
  // The problem also occurs on some Linux (Chromium) and Android (Chrome) devices.
  window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
      // Do any checks and updates to the page
      window.location.reload( true );
    }
  });

  function addListenerGlobalBeforeunload(event) {
    //window.removeEventListener('beforeunload', addListenerGlobalBeforeunload);
    //event.preventDefault();
    if (navbar_type == 'left') {
      closeMbExtruder(updateCookie = true);
    }

    if (mainContent) {
      if (mainContentJ.css('display') == 'flex') {
        // If flex-grow is set to a value > 0 then "height" will be ignored!
        mainContentJ.css({flex: "0 1 auto"});
      }
      if (typeof ZM_WEB_ANIMATIONS === 'undefined' || !ZM_WEB_ANIMATIONS) {
        mainContentJ.css({display: "none"});
      } else {
        mainContentJ.animate({height: 0}, 300, function rollupBeforeunloadPage() {
          mainContentJ.css({display: "none"});
        });
      }
    }
    //event.returnValue = '';
  }
  window.addEventListener('beforeunload', addListenerGlobalBeforeunload);

  document.querySelectorAll('[id ^= "controlMute"]').forEach(function(el) {
    el.addEventListener("click", function clickControlMute(event) {
      const mid = (stringToNumber(event.target.id) || stringToNumber(document.querySelector('[id ^= "liveStream"]').id));
      if (!mid) return;
      if (currentView == 'watch') {
        monitorStream.controlMute('switch');
      } else if (currentView == 'montage') {
        const currentMonitor = monitors.find((o) => {
          return parseInt(o["id"]) === mid;
        });
        currentMonitor.controlMute('switch');
      }
    });
  });
}

// Called when monitor filters change - refreshes table via AJAX instead of full page reload
function monitorFilterOnChange(element) {
  // Save filter values to cookies for persistence
  var form = (element && element.form) ? element.form : document.forms['monitorFiltersForm'];
  if (form) {
    console.log('have form', element, form);
    // Define filter fields to save (using var names without [] suffix for consistency)
    var filterFields = [
      {name: 'GroupId[]', cookieName: 'GroupId'},
      {name: 'ServerId[]', cookieName: 'ServerId'},
      {name: 'StorageId[]', cookieName: 'StorageId'},
      {name: 'Status[]', cookieName: 'Status'},
      {name: 'Capturing[]', cookieName: 'Capturing'},
      {name: 'Analysing[]', cookieName: 'Analysing'},
      {name: 'Recording[]', cookieName: 'Recording'},
      {name: 'MonitorId[]', cookieName: 'MonitorId'},
      {name: 'MonitorName', cookieName: 'MonitorName'},
      {name: 'Source', cookieName: 'Source'}
    ];

    filterFields.forEach(function(fieldInfo) {
      var field = form.elements[fieldInfo.name];
      if (field) {
        // Check if it's a multi-value field (ends with [] or is select-multiple)
        var isMultiValue = fieldInfo.name.endsWith('[]') || field.multiple || field.type === 'select-multiple';

        if (isMultiValue) {
          // Handle multi-select dropdowns and array fields
          var selected = $j(field).val();
          if (selected && selected.length > 0) {
            setCookie('zmFilter_' + fieldInfo.cookieName, JSON.stringify(selected));
          } else {
            setCookie('zmFilter_' + fieldInfo.cookieName, '');
          }
        } else if (field.type === 'text') {
          // Handle text inputs
          setCookie('zmFilter_' + fieldInfo.cookieName, field.value);
        }
      }
    });
  } else {
    console.log('do not have form', element);
  }

  // On console view with bootstrap-table, just refresh the table
  if (typeof table !== 'undefined' && table.length) {
    table.bootstrapTable('refresh');
  } else {
    // Fall back to full page reload on other views
    submitThisForm(element);
  }
}

$j( window ).on("load", initPageGeneral);
