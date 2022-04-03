var backBtn = $j('#backBtn');
var exportBtn = $j('#exportBtn');
var deleteBtn = $j('#deleteBtn');
var table = $j('#snapshotTable');

/*
This is the format of the json object sent by bootstrap-table

var params =
{
"type":"get",
"data":
  {
  "search":"some search text",
  "sort":"StartDateTime",
  "order":"asc",
  "offset":0,
  "limit":25
  "filter":
    {
    "Name":"some advanced search text"
    "StartDateTime":"some more advanced search text"
    }
  },
"cache":true,
"contentType":"application/json",
"dataType":"json"
};
*/

// Called by bootstrap-table to retrieve zm event data
function ajaxRequest(params) {
  if ( params.data && params.data.filter ) {
    params.data.advsearch = params.data.filter;
    delete params.data.filter;
  }
  $j.getJSON(thisUrl + '?view=request&request=snapshots&task=query', params.data)
      .done(function(data) {
        var rows = processRows(data.rows);
        // rearrange the result into what bootstrap-table expects
        params.success({total: data.total, totalNotFiltered: data.totalNotFiltered, rows: rows});
      })
      .fail(function(jqXHR) {
        logAjaxFail(jqXHR);
        table.bootstrapTable('refresh');
      });
}

function processRows(rows) {
  $j.each(rows, function(ndx, row) {
    var id = row.Id;
    row.Id = '<a href="?view=snapshot&amp;id=' + id + '">' + id + '</a>';
    row.Name = '<a href="?view=snapshot&amp;id=' + id + '">' + row.Name + '</a>';
    row.Description = '<a href="?view=snapshot&amp;id=' + id + '">' + row.Description + '</a>';

    if (WEB_LIST_THUMBS) row.Thumbnail = '<a href="?view=snapshot&amp;id=' + id +'">' + row.imgHtml + '</a>';
  });

  return rows;
}

// Returns the event id's of the selected rows
function getIdSelections() {
  var table = $j('#snapshotTable');

  return $j.map(table.bootstrapTable('getSelections'), function(row) {
    return row.Id.replace(/(<([^>]+)>)/gi, ''); // strip the html from the element before sending
  });
}

// Returns a boolen to indicate at least one selected row is archived
function getArchivedSelections() {
  var table = $j('#snapshotTable');
  var selection = $j.map(table.bootstrapTable('getSelections'), function(row) {
    return row.Archived;
  });
  return selection.includes("Yes");
}

// Load the Delete Confirmation Modal HTML via Ajax call
function getDelConfirmModal() {
  $j.getJSON(thisUrl + '?request=modal&modal=delconfirm')
      .done(function(data) {
        insertModalHtml('deleteConfirm', data.html);
        manageDelConfirmModalBtns();
      })
      .fail(logAjaxFail);
}

// Manage the DELETE CONFIRMATION modal button
function manageDelConfirmModalBtns() {
  document.getElementById("delConfirmBtn").addEventListener("click", function onDelConfirmClick(evt) {
    if (!canEdit.Events) {
      enoperm();
      return;
    }

    var selections = getIdSelections();

    evt.preventDefault();
    $j.getJSON(thisUrl + '?request=snapshots&task=delete&ids[]='+selections.join('&ids[]='))
        .done( function(data) {
          $j('#snapshotTable').bootstrapTable('refresh');
          $j('#deleteConfirm').modal('hide');
        })
        .fail( function(jqxhr) {
          logAjaxFail(jqxhr);
          $j('#snapshotTable').bootstrapTable('refresh');
          $j('#deleteConfirm').modal('hide');
        });
  });

  // Manage the CANCEL modal button
  document.getElementById("delCancelBtn").addEventListener("click", function onDelCancelClick(evt) {
    $j('#deleteConfirm').modal('hide');
  });
}

function getEventDetailModal(eid) {
  $j.getJSON(thisUrl + '?request=modal&modal=eventdetail&eids[]=' + eid)
      .done(function(data) {
        insertModalHtml('eventDetailModal', data.html);
        $j('#eventDetailModal').modal('show');
        // Manage the Save button
        $j('#eventDetailSaveBtn').click(function(evt) {
          evt.preventDefault();
          $j('#eventDetailForm').submit();
        });
      })
      .fail(logAjaxFail);
}

function getObjdetectModal(eid) {
  $j.getJSON(thisUrl + '?request=modal&modal=objdetect&eid=' + eid)
      .done(function(data) {
        insertModalHtml('objdetectModal', data.html);
        $j('#objdetectModal').modal('show');
      })
      .fail(logAjaxFail);
}

function initPage() {
  // Remove the thumbnail column from the DOM if thumbnails are off globally
  if ( !WEB_LIST_THUMBS ) $j('th[data-field="Thumbnail"]').remove();

  // Load the delete confirmation modal into the DOM
  getDelConfirmModal();

  // Init the bootstrap-table
  table.bootstrapTable({icons: icons});

  // enable or disable buttons based on current selection and user rights
  table.on('check.bs.table uncheck.bs.table ' +
  'check-all.bs.table uncheck-all.bs.table',
  function() {
    selections = table.bootstrapTable('getSelections');

    //exportBtn.prop('disabled', !(selections.length && canView.Events));
    //downloadBtn.prop('disabled', !(selections.length && canView.Events));
    deleteBtn.prop('disabled', !(selections.length && canEdit.Events));
  });

  // Don't enable the back button if there is no previous zm page to go back to
  backBtn.prop('disabled', !document.referrer.length);

  // Setup the thumbnail video animation
  //initThumbAnimation();

  // Some toolbar events break the thumbnail animation, so re-init eventlistener
  //table.on('all.bs.table', initThumbAnimation);

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

  /*
  // Manage the ARCHIVE button
  document.getElementById("archiveBtn").addEventListener("click", function onArchiveClick(evt) {
    var selections = getIdSelections();

    evt.preventDefault();
    $j.getJSON(thisUrl + '?request=events&task=archive&eids[]='+selections.join('&eids[]='))
        .done( function(data) {
          $j('#snapshotTable').bootstrapTable('refresh');
        })
        .fail(logAjaxFail);
  });

  // Manage the UNARCHIVE button
  document.getElementById("unarchiveBtn").addEventListener("click", function onUnarchiveClick(evt) {
    if ( ! canEdit.Events ) {
      enoperm();
      return;
    }

    var selections = getIdSelections();
    //console.log(selections);

    evt.preventDefault();
    $j.getJSON(thisUrl + '?request=events&task=unarchive&eids[]='+selections.join('&eids[]='))
        .done( function(data) {
          $j('#snapshotTable').bootstrapTable('refresh');
        })
        .fail(logAjaxFail);
  });

  // Manage the EDIT button
  document.getElementById("editBtn").addEventListener("click", function onEditClick(evt) {
    if ( ! canEdit.Events ) {
      enoperm();
      return;
    }

    var selections = getIdSelections();

    evt.preventDefault();
    $j.getJSON(thisUrl + '?request=modal&modal=eventdetail&eids[]='+selections.join('&eids[]='))
        .done(function(data) {
          insertModalHtml('eventDetailModal', data.html);
          $j('#eventDetailModal').modal('show');
          // Manage the Save button
          $j('#eventDetailSaveBtn').click(function(evt) {
            evt.preventDefault();
            $j('#eventDetailForm').submit();
          });
        })
        .fail(logAjaxFail);
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
    $j.getJSON(thisUrl + '?request=modal&modal=download&eids[]='+selections.join('&eids[]='))
        .done(function(data) {
          insertModalHtml('downloadModal', data.html);
          $j('#downloadModal').modal('show');
          // Manage the GENERATE DOWNLOAD button
          $j('#exportButton').click(exportEvent);
        })
        .fail(logAjaxFail);
  });
  */
  // Manage the DELETE button
  document.getElementById("deleteBtn").addEventListener("click", function onDeleteClick(evt) {
    if ( ! canEdit.Events ) {
      enoperm();
      return;
    }

    evt.preventDefault();
    $j('#deleteConfirm').modal('show');
  });

  // Update table links each time after new data is loaded
  table.on('post-body.bs.table', function(data) {
    var thumb_ndx = $j('#snapshotTable tr th').filter(function() {
      return $j(this).text().trim() == 'Thumbnail';
    }).index();
    table.find("tr td:nth-child(" + (thumb_ndx+1) + ")").addClass('colThumbnail');
  });

  table.bootstrapTable('resetSearch');
  // The table is initially given a hidden style, so now that we are done rendering, show it
  table.show();
}

$j(document).ready(function() {
  initPage();
});
