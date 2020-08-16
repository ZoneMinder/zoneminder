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

// Returns the event id's of the selected rows
function getIdSelections() {
  var table = $j('#eventTable');

  return $j.map(table.bootstrapTable('getSelections'), function(row) {
    return row.Id.replace(/(<([^>]+)>)/gi, ""); // strip the html from the element before sending
  });
}

// Returns a boolen to indicate at least one selected row is archived
function getArchivedSelections() {
  var table = $j('#eventTable');
  var selection = $j.map(table.bootstrapTable('getSelections'), function(row) {
    return row.Archived;
  });
  return selection.includes("Yes");
}

function initPage() {
  var viewBtn = $j('#viewBtn');
  var archiveBtn = $j('#archiveBtn');
  var unarchiveBtn = $j('#unarchiveBtn');
  var editBtn = $j('#editBtn');
  var exportBtn = $j('#exportBtn');
  var downloadBtn = $j('#downloadBtn');
  var deleteBtn = $j('#deleteBtn');
  var table = $j('#eventTable');

  $j('#eventTable').bootstrapTable();
  table.bootstrapTable('hideColumn', 'Archived');
  table.on('check.bs.table uncheck.bs.table ' +
  'check-all.bs.table uncheck-all.bs.table',
  function() {
    // enable or disable buttons based on current selection and user rights
    viewBtn.prop('disabled', !(table.bootstrapTable('getSelections').length && canViewEvents));
    archiveBtn.prop('disabled', !(table.bootstrapTable('getSelections').length && canEditEvents));
    unarchiveBtn.prop('disabled', !(getArchivedSelections()) && canEditEvents);
    editBtn.prop('disabled', !(table.bootstrapTable('getSelections').length && canEditEvents));
    exportBtn.prop('disabled', !(table.bootstrapTable('getSelections').length && canViewEvents));
    downloadBtn.prop('disabled', !(table.bootstrapTable('getSelections').length && canViewEvents));
    deleteBtn.prop('disabled', !(table.bootstrapTable('getSelections').length && canEditEvents));
  });
  if ( window.history.length == 1 ) {
    $j('#controls').children().eq(0).html('');
  }
  $j('.colThumbnail img').each(function() {
    this.addEventListener('mouseover', thumbnail_onmouseover, false);
    this.addEventListener('mouseout', thumbnail_onmouseout, false);
  });
  // Manage the BACK button
  document.getElementById("backBtn").addEventListener("click", function onBackClick(evt) {
    evt.preventDefault();
    window.history.back();
  });
  // Manage the REFRESH Button
  document.getElementById("refreshBtn").addEventListener("click", function onRefreshClick(evt) {
    evt.preventDefault();
    window.location.reload(true);
  });
  // Manage the TIMELINE Button
  document.getElementById("tlineBtn").addEventListener("click", function onTlineClick(evt) {
    evt.preventDefault();
    window.location.assign('?view=timeline'+filterQuery);
  });
  // Manage the VIEW button
  document.getElementById("viewBtn").addEventListener("click", function onViewClick(evt) {
    var selections = getIdSelections();

    evt.preventDefault();
    var filter = '&filter[Query][terms][0][attr]=Id&filter[Query][terms][0][op]=%3D%5B%5D&filter[Query][terms][0][val]='+selections.join('%2C');
    window.location.href = thisUrl+'?view=event&eid='+selections[0]+filter+sortQuery+'&page=1&play=1';
  });
  // Manage the ARCHIVE button
  document.getElementById("archiveBtn").addEventListener("click", function onArchiveClick(evt) {
    var selections = getIdSelections();

    evt.preventDefault();
    $j.getJSON(thisUrl + '?view=events&action=archive&eids_json='+JSON.stringify(selections));
    window.location.reload(true);
  });
  // Manage the UNARCHIVE button
  document.getElementById("unarchiveBtn").addEventListener("click", function onUnarchiveClick(evt) {
    if ( ! canEditEvents ) {
      alert("You do not have permission to Unarchive events.");
      return;
    }

    var selections = getIdSelections();

    evt.preventDefault();
    $j.getJSON(thisUrl + '?view=events&action=unarchive&eids_json='+JSON.stringify(selections));

    if ( openFilterWindow ) {
      //opener.location.reload(true);
      createPopup( '?view=filter&page='+thisPage+filterQuery, 'zmFilter', 'filter' );
      location.replace( '?view='+currentView+'&page='+thisPage+filterQuery );
    } else {
      window.location.reload(true);
    }
  });
  // Manage the EDIT button
  document.getElementById("editBtn").addEventListener("click", function onEditClick(evt) {
    if ( ! canEditEvents ) {
      alert("You do not have permission to edit events.");
      return;
    }

    var selections = getIdSelections();

    evt.preventDefault();
    createPopup('?view=eventdetail&eids[]='+selections.join('&eids[]='), 'zmEventDetail', 'eventdetail');
  });
  // Manage the EXPORT button
  document.getElementById("exportBtn").addEventListener("click", function onExportClick(evt) {
    var selections = getIdSelections();

    evt.preventDefault();
    window.location.assign('?view=export&eids[]='+selections.join('&eids[]='));
  });
  // Manage the DOWNLOAD VIDEO button
  document.getElementById("downloadBtn").addEventListener("click", function onDownloadClick(evt) {
    var selections = getIdSelections();

    evt.preventDefault();
    createPopup('?view=download&eids[]='+selections.join('&eids[]='), 'zmDownload', 'download');
  });
  // Manage the DELETE button
  document.getElementById("deleteBtn").addEventListener("click", function onDeleteClick(evt) {
    if ( ! canEditEvents ) {
      alert("You do not have permission to delete events.");
      return;
    }

    var selections = getIdSelections();

    evt.preventDefault();
    $j.getJSON(thisUrl + '?view=events&action=delete&eids[]='+selections.join('&eids[]='));
    window.location.reload(true);
  });
}

$j(document).ready(function() {
  initPage();
});
