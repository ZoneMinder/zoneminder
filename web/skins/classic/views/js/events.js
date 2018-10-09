function closeWindows() {
  window.close();
  // This is a hack. The only way to close an existing window is to try and open it!
  var filterWindow = window.open( thisUrl+'?view=none', 'zmFilter', 'width=1,height=1' );
  filterWindow.close();
}

function toggleCheckbox( element, name ) {
  var form = element.form;
  var checked = element.checked;
  for (var i = 0; i < form.elements.length; i++)
    if (form.elements[i].name.indexOf(name) == 0)
      form.elements[i].checked = checked;
  form.viewBtn.disabled = !(canViewEvents && checked);
  form.editBtn.disabled = !(canEditEvents && checked);
  form.archiveBtn.disabled = unarchivedEvents?!checked:true;
  form.unarchiveBtn.disabled = !(canEditEvents && archivedEvents && checked);
  form.downloadBtn.disabled = !(canViewEvents && checked);
  form.exportBtn.disabled = !(canViewEvents && checked);
  form.deleteBtn.disabled = !(canEditEvents && checked);
}

function configureButton( element, name ) {
  var form = element.form;
  var checked = element.checked;
  if ( !checked ) {
    for (var i = 0; i < form.elements.length; i++) {
      if ( form.elements[i].name.indexOf(name) == 0) {
        if ( form.elements[i].checked ) {
          checked = true;
          break;
        }
      }
    }
  }
  if ( !element.checked )
    form.toggleCheck.checked = false;
  form.viewBtn.disabled = !(canViewEvents && checked);
  form.editBtn.disabled = !(canEditEvents && checked);
  form.archiveBtn.disabled = (!checked)||(!unarchivedEvents);
  form.unarchiveBtn.disabled = !(canEditEvents && checked && archivedEvents);
  form.downloadBtn.disabled = !(canViewEvents && checked);
  form.exportBtn.disabled = !(canViewEvents && checked);
  form.deleteBtn.disabled = !(canEditEvents && checked);
}

function deleteEvents( element, name ) {
  if ( ! canEditEvents ) {
    alert("You do not have permission to delete events.");
    return;
  }
  var form = element.form;
  var count = 0;
  for (var i = 0; i < form.elements.length; i++) {
    if (form.elements[i].name.indexOf(name) == 0) {
      if ( form.elements[i].checked ) {
        count++;
        break;
      }
    }
  }
  if ( count > 0 ) {
    if ( confirm( confirmDeleteEventsString ) ) {
      form.elements['action'].value = 'delete';
      form.submit();
    }
  }
}

function editEvents( element, name ) {
  if ( ! canEditEvents ) {
    alert("You do not have permission to delete events.");
    return;
  }
  var form = element.form;
  var eids = new Array();
  for (var i = 0; i < form.elements.length; i++) {
    if (form.elements[i].name.indexOf(name) == 0) {
      if ( form.elements[i].checked ) {
        eids[eids.length] = 'eids[]='+form.elements[i].value;
      }
    }
  }
  createPopup( '?view=eventdetail&'+eids.join( '&' ), 'zmEventDetail', 'eventdetail' );
}

function downloadVideo( element, name ) {
  var form = element.form;
  var eids = new Array();
  for (var i = 0; i < form.elements.length; i++) {
    if (form.elements[i].name.indexOf(name) == 0) {
      if ( form.elements[i].checked ) {
        eids[eids.length] = 'eids[]='+form.elements[i].value;
      }
    }
  }
  createPopup( '?view=download&'+eids.join( '&' ), 'zmDownload', 'download' );
}

function exportEvents( element, name ) {
  var form = element.form;
  var eids = new Array();
  for (var i = 0; i < form.elements.length; i++) {
    if (form.elements[i].name.indexOf(name) == 0) {
      if ( form.elements[i].checked ) {
        eids[eids.length] = 'eids[]='+form.elements[i].value;
      }
    }
  }
  createPopup( '?view=export&'+eids.join( '&' ), 'zmExport', 'export' );
}

function viewEvents( element, name ) {
  var form = element.form;
  var events = new Array();
  for (var i = 0; i < form.elements.length; i++) {
    if ( form.elements[i].name.indexOf(name) == 0) {
      if ( form.elements[i].checked ) {
        events[events.length] = form.elements[i].value;
      }
    }
  }
  if ( events.length > 0 ) {
    let filter = '&filter[Query][terms][0][attr]=Id&filter[Query][terms][0][op]=%3D%5B%5D&filter[Query][terms][0][val]='+events.join('%2C');
    window.location.href = thisUrl+'?view=event&eid='+events[0]+filter+sortQuery+'&page=1&play=1';
  }
}

function archiveEvents( element, name ) {
  var form = element.form;
  form.elements['action'].value = 'archive';
  form.submit();
}

function unarchiveEvents(element, name) {
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

function initPage () {
  if (window.history.length == 1) {
    $j('#controls').children().eq(0).html('');
  }
}

$j(document).ready(initPage);
