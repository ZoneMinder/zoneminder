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
    $j("[data-flip-сontrol-object]").click(function() {
      const _this_ = $j(this);
      const objIconButton = _this_.find("i");
      const obj = $j(_this_.attr('data-flip-сontrol-object'));

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
    $j("[data-flip-сontrol-object]").each(function() { //let's go through all objects (buttons) and set icons
      const _this_ = $j(this);
      const сookie = getCookie('zmFilterBarFlip'+_this_.attr('data-flip-сontrol-object'));
      const initialStateIcon = _this_.attr('data-initial-state-icon'); //"visible"=Opened block , "hidden"=Closed block or "undefined"=use cookie
      const objIconButton = _this_.find("i");
      const obj = $j(_this_.attr('data-flip-сontrol-object'));

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

    // Manage the web console filter bar minimize chevron
    /*$j("#mfbflip").click(function() {
      $j("#mfbpanel").slideToggle("slow", function() {
        if ($j.isFunction('changeScale')) {
          changeScale();
        }
      });
      var mfbflip = $j("#mfbflip");
      if ( mfbflip.html() == 'keyboard_arrow_up' ) {
        mfbflip.html('keyboard_arrow_down');
        setCookie('zmMonitorFilterBarFlip', 'up');
      } else {
        mfbflip.html('keyboard_arrow_up');
        setCookie('zmMonitorFilterBarFlip', 'down');
        $j('.chosen').chosen("destroy");
        $j('.chosen').chosen();
      }
    });*/
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
    const obj = $j(pressedBtn.attr('data-flip-сontrol-object'));
    if ((visibility == "visible") || (obj.is(":visible") && !obj.hasClass("hidden-shift"))) {
      if (objIconButton.is('[class~="material-icons"]')) { // use material-icons
        objIconButton.html(objIconButton.attr('data-icon-hidden'));
      } else if (objIconButton.is('[class*="fa-"]')) { //use Font Awesome
        objIconButton.removeClass(objIconButton.attr('data-icon-visible')).addClass(objIconButton.attr('data-icon-hidden'));
      }
      setCookie('zmFilterBarFlip'+pressedBtn.attr('data-flip-сontrol-object'), 'hidden');
    } else { //hidden
      obj.removeClass('hidden-shift').addClass('hidden'); //It is necessary to make the block invisible both for JS and for humans
      if (objIconButton.is('[class~="material-icons"]')) { // use material-icons
        objIconButton.html(objIconButton.attr('data-icon-visible'));
      } else if (objIconButton.is('[class*="fa-"]')) { //use Font Awesome
        objIconButton.removeClass(objIconButton.attr('data-icon-hidden')).addClass(objIconButton.attr('data-icon-visible'));
      }
      setCookie('zmFilterBarFlip'+pressedBtn.attr('data-flip-сontrol-object'), 'visible');
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
  resizeTimer = setTimeout(changeScale, 250);
}

/* scaleToFit
 *
 * Tries to figure out the available space to fit an image into
 * Uses the #content element
 * figures out where bottomEl is in the viewport
 * does calculations
 * scaleEl is the thing to be scaled, should be a jquery object and should have height
 * */
function scaleToFit(baseWidth, baseHeight, scaleEl, bottomEl, container, panZoomScale = 1) {
  $j(window).on('resize', endOfResize); //set delayed scaling when Scale to Fit is selected
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
  /* IgorA100 not required due to new "Scale" algorithm & new PanZoom (may 2024)
  const scales = $j('#scale option').map(function() {
    return parseInt($j(this).val());
  }).get();
  scales.shift(); // pop off Scale To Fit
  let closest = null;
  $j(scales).each(function() { //Set zms scale to nearest regular scale.  Zoom does not like arbitrary scale values.
    if (closest == null || Math.abs(this - autoScale) < Math.abs(closest - autoScale)) {
      closest = this.valueOf();
    }
  });
  if (closest) {
    console.log("Setting to closest: " + closest + " instead of " + autoScale);
    autoScale = closest;
  }
  */
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

function logAjaxFail(jqxhr, textStatus, error) {
  console.log("Request Failed: " + textStatus + ", " + error);
  if ( ! jqxhr.responseText ) {
    console.log("Ajax request failed.  No responseText.  jqxhr follows:\n", jqxhr);
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
var thumbnail_timeout;
function thumbnail_onmouseover(event) {
  const img = event.target;
  const imgClass = ( currentView == 'console' ) ? 'zoom-console' : 'zoom';
  const imgAttr = ( currentView == 'frames' ) ? 'full_img_src' : 'stream_src';
  img.src = img.getAttribute(imgAttr);
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

function applyChosen() {
  const limit_search_threshold = 10;

  $j('.chosen').chosen('destroy');
  $j('.chosen').not('.chosen-full-width, .chosen-auto-width').chosen({allow_single_deselect: true, disable_search_threshold: limit_search_threshold, search_contains: true});
  $j('.chosen.chosen-full-width').chosen({allow_single_deselect: true, disable_search_threshold: limit_search_threshold, search_contains: true, width: "100%"});
  $j('.chosen.chosen-auto-width').chosen({allow_single_deselect: true, disable_search_threshold: limit_search_threshold, search_contains: true, width: "auto"});
}

function stringToNumber(str) {
  return parseInt(str.replace(/\D/g, ''));
}

function loadFontFaceObserver() {
  const font = new FontFaceObserver('Material Icons', {weight: 400});
  font.load().then(function() {
    $j('.material-icons').css('display', 'inline-block');
  }, function() {
    $j('.material-icons').css('display', 'inline-block');
  });
}

function thisClickOnStreamObject(clickObj) {
  if (clickObj.id) {
    if (clickObj.id.indexOf('evtStream') != -1 || clickObj.id.indexOf('liveStream') != -1) {
      return true;
    } else if (clickObj.id.indexOf('monitorStatus') != -1) {
      return document.getElementById('monitor'+stringToNumber(clickObj.id));
      //return clickObj;
    } else if (clickObj.id.indexOf('videoobj') != -1) {
      return document.getElementById('eventVideo');
    } else return false;
  } else return false;
}

/* For mobile device Not implemented yet. */
function thisClickOnTimeline(clickObj) {
  console.log("thisClickOnTimeline_clickObj=====++>", clickObj);
  return false;
}

var doubleTouchExecute = function(event, touchEvent) {
console.log("touchEvent=====++>", touchEvent);
console.log("event=====++>", event);
console.log("this=====++>", this);
//  if (touchEvent.target.id &&
//    (touchEvent.target.id.indexOf('evtStream') != -1 || touchEvent.target.id.indexOf('liveStream') != -1 || touchEvent.target.id.indexOf('monitorStatus') != -1)) {
  if (thisClickOnStreamObject(touchEvent.target)) {
    doubleClickOnStream(event, touchEvent);
  } else if (thisClickOnTimeline(touchEvent.target)) {
    doubleTouchOnTimeline(event, touchEvent);
  }
};

/* For mobile device Not implemented yet. */
var doubleTouchOnTimeline = function(event, touchEvent) {
  console.log("+++doubleTouchOnTimeline_event==>", event);
  console.log("+++doubleTouchOnTimeline_touchEvent==>", touchEvent);
};

var doubleClickOnStream = function(event, touchEvent) {
  //console.log("+++shifted==>", shifted);
  //console.log("+++ctrled==>", ctrled);
  //console.log("+++alted==>", alted);
  if (shifted || ctrled || alted) return;
  let target = null;
//console.log("touchEvent=====++>", touchEvent);
//console.log("event=====++>", event);
//console.log("this=====++>", this);
  if (event.target) {// Click NOT on touch screen, use THIS
////console.log("event.target.id=====++>", event.target.id);
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
//console.log("target=====++>", target);

  if (target) {
    if (document.fullscreenElement) {
      closeFullscreen();
    } else {
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

$j(document).on('keyup.global keydown.global', function(e) {
  shifted = e.shiftKey ? e.shiftKey : e.shift;
  ctrled = e.ctrlKey;
  alted = e.altKey;
});

loadFontFaceObserver();
