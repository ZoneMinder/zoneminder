function closeWindows() {
  window.close();
  // This is a hack. The only way to close an existing window is to try and open it!
  var filterWindow = window.open( thisUrl+'?view=none', 'zmFilter', 'width=1,height=1' );
  filterWindow.close();
}

function setButtonStates( element ) {
  var form = element.form;
  var checked = element.checked;
  form.viewBtn.disabled = !(canViewEvents && checked);
  form.editBtn.disabled = !(canEditEvents && checked);
  form.archiveBtn.disabled = unarchivedEvents?!checked:true;
  form.unarchiveBtn.disabled = !(canEditEvents && archivedEvents && checked);
  form.downloadBtn.disabled = !(canViewEvents && checked);
  form.exportBtn.disabled = !(canViewEvents && checked);
  form.deleteBtn.disabled = !(canEditEvents && checked);
}

function configureButton(event) {
  var element = event.target;
  var form = element.form;
  var checked = element.checked;
  if ( !checked ) {
    for (var i = 0, len=form.elements.length; i < len; i++) {
      if ( form.elements[i].name.indexOf('eids') == 0) {
        if ( form.elements[i].checked ) {
          checked = true;
          break;
        }
      }
    }
  }
  if ( !element.checked ) {
    form.toggleCheck.checked = false;
  }
  form.viewBtn.disabled = !(canViewEvents && checked);
  form.editBtn.disabled = !(canEditEvents && checked);
  form.archiveBtn.disabled = (!checked)||(!unarchivedEvents);
  form.unarchiveBtn.disabled = !(canEditEvents && checked && archivedEvents);
  form.downloadBtn.disabled = !(canViewEvents && checked);
  form.exportBtn.disabled = !(canViewEvents && checked);
  form.deleteBtn.disabled = !(canEditEvents && checked);
}

function deleteEvents( element ) {
  if ( ! canEditEvents ) {
    alert("You do not have permission to delete events.");
    return;
  }
  var form = element.form;

  var count = 0;
  // This is slightly more efficient than a jquery selector because we stop after finding one.
  for (var i = 0; i < form.elements.length; i++) {
    if (form.elements[i].name.indexOf('eids') == 0) {
      if ( form.elements[i].checked ) {
        count++;
        break;
      }
    }
  }
  if ( count > 0 ) {
    if ( confirm(confirmDeleteEventsString) ) {
      form.elements['action'].value = 'delete';
      form.submit();
    }
  }
}

function editEvents( element ) {
  if ( ! canEditEvents ) {
    alert("You do not have permission to delete events.");
    return;
  }
  var form = element.form;
  var eids = new Array();
  for (var i = 0, len=form.elements.length; i < len; i++) {
    if (form.elements[i].name.indexOf('eids') == 0) {
      if ( form.elements[i].checked ) {
        eids[eids.length] = 'eids[]='+form.elements[i].value;
      }
    }
  }
  createPopup('?view=eventdetail&'+eids.join('&'), 'zmEventDetail', 'eventdetail');
}

function downloadVideo( element ) {
  var form = element.form;
  var eids = new Array();
  for (var i = 0, len=form.elements.length; i < len; i++) {
    if (form.elements[i].name.indexOf('eids') == 0 ) {
      if ( form.elements[i].checked ) {
        eids[eids.length] = 'eids[]='+form.elements[i].value;
      }
    }
  }
  createPopup( '?view=download&'+eids.join('&'), 'zmDownload', 'download' );
}

function exportEvents( element ) {
  var form = element.form;
  console.log(form);
  form.action = '?view=export';
  form.elements['view'].value='export';
  form.submit();
}

function viewEvents( element ) {
  var form = element.form;
  var events = new Array();
  for (var i = 0, len=form.elements.length; i < len; i++) {
    if ( form.elements[i].name.indexOf('eids') == 0 ) {
      if ( form.elements[i].checked ) {
        events[events.length] = form.elements[i].value;
      }
    }
  }
  if ( events.length > 0 ) {
    var filter = '&filter[Query][terms][0][attr]=Id&filter[Query][terms][0][op]=%3D%5B%5D&filter[Query][terms][0][val]='+events.join('%2C');
    window.location.href = thisUrl+'?view=event&eid='+events[0]+filter+sortQuery+'&page=1&play=1';
  }
}

function archiveEvents(element) {
  var form = element.form;
  form.elements['action'].value = 'archive';
  form.submit();
}

function unarchiveEvents(element) {
  if ( ! canEditEvents ) {
    alert("You do not have permission to delete events.");
    return;
  }
  var form = element.form;
  form.elements['action'].value = 'unarchive';
  form.submit();
}

if ( openFilterWindow ) {
  //opener.location.reload(true);
  createPopup( '?view=filter&page='+thisPage+filterQuery, 'zmFilter', 'filter' );
  location.replace( '?view='+currentView+'&page='+thisPage+filterQuery );
}

function thumbnail_onmouseover(event) {
  var img = event.target;
  img.src = '';
  img.src = img.getAttribute('stream_src');
}
function thumbnail_onmouseout(event) {
  var img = event.target;
  img.src = '';
  img.src = img.getAttribute('still_src');
}

function initPage() {
  if ( window.history.length == 1 ) {
    $j('#controls').children().eq(0).html('');
  }
  $j('.colThumbnail img').each(function() {
    this.addEventListener('mouseover', thumbnail_onmouseover, false);
    this.addEventListener('mouseout', thumbnail_onmouseout, false);
  });
  $j('input[name=eids\\[\\]]').each(function() {
    this.addEventListener('click', configureButton, false);
  });
  document.getElementById("refreshLink").addEventListener("click", function onRefreshClick(evt) {
    evt.preventDefault();
    window.location.reload(true);
  });
  document.getElementById("backLink").addEventListener("click", function onBackClick(evt) {
    evt.preventDefault();
    window.history.back();
  });
}

$j(document).ready(initPage);
