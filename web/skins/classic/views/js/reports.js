var backBtn = $j('#backBtn');
var viewBtn = $j('#viewBtn');
var editBtn = $j('#editBtn');
var deleteBtn = $j('#deleteBtn');
var table = $j('#reportsTable');

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
  if (params.data && params.data.filter) {
    params.data.advsearch = params.data.filter;
    delete params.data.filter;
  }
  $j.getJSON(thisUrl + '?view=request&request=reports&task=query', params.data)
      .done(function(data) {
        if (data.result == 'Error') {
          alert(data.message);
          return;
        }
        var rows = processRows(data.rows);
        // rearrange the result into what bootstrap-table expects
        params.success({total: data.total, totalNotFiltered: data.totalNotFiltered, rows: rows});
      })
      .fail(function(jqXHR) {
        logAjaxFail(jqXHR);
        $j('#reportsTable').bootstrapTable('refresh');
      });
}

function processRows(rows) {
  $j.each(rows, function(ndx, row) {
    const id = row.Id;

    row.Id = '<a href="?view=report&amp;id=' + id + '&amp;page=1">' + id + '</a>';
    row.Name = '<a href="?view=report&amp;id=' + id + '&amp;page=1">' + row.Name + '</a>';
  });

  return rows;
}

// Returns the event id's of the selected rows
function getIdSelections() {
  var table = $j('#reportsTable');

  return $j.map(table.bootstrapTable('getSelections'), function(row) {
    return row.Id.replace(/(<([^>]+)>)/gi, ''); // strip the html from the element before sending
  });
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
  document.getElementById("delConfirmBtn").addEventListener('click', function onDelConfirmClick(evt) {
    if ( ! canEdit.Events ) {
      enoperm();
      return;
    }
    evt.preventDefault();

    const selections = getIdSelections();
    if (!selections.length) {
      alert('Please select reports to delete.');
    } else {
      deleteReports(selections);
    }
  });

  // Manage the CANCEL modal button
  document.getElementById("delCancelBtn").addEventListener('click', function onDelCancelClick(evt) {
    $j('#deleteConfirm').modal('hide');
  });
}

function deleteReports(ids) {
  const ticker = document.getElementById('deleteProgressTicker');
  const chunk = ids.splice(0, 10);
  console.log("Deleting " + chunk.length + " selections.  " + ids.length);

  $j.getJSON(thisUrl + '?request=reports&task=delete&ids[]='+chunk.join('&ids[]='))
      .done( function(data) {
        if (!ids.length) {
          $j('#reportsTable').bootstrapTable('refresh');
          $j('#deleteConfirm').modal('hide');
        } else {
          if (ticker.innerHTML.length < 1 || ticker.innerHTML.length > 10) {
            ticker.innerHTML = '.';
          } else {
            ticker.innerHTML = ticker.innerHTML + '.';
          }
          deleteReports(ids);
        }
      })
      .fail( function(jqxhr) {
        logAjaxFail(jqxhr);
        $j('#reportsTable').bootstrapTable('refresh');
        $j('#deleteConfirm').modal('hide');
      });
}

function initPage() {
  // Load the delete confirmation modal into the DOM
  getDelConfirmModal();

  // Init the bootstrap-table
  table.bootstrapTable({icons: icons});

  // enable or disable buttons based on current selection and user rights
  table.on('check.bs.table uncheck.bs.table ' +
  'check-all.bs.table uncheck-all.bs.table',
  function() {
    selections = table.bootstrapTable('getSelections');

    viewBtn.prop('disabled', !(selections.length && canView.Events));
    deleteBtn.prop('disabled', !(selections.length && canEdit.Events));
  });

  // Don't enable the back button if there is no previous zm page to go back to
  backBtn.prop('disabled', !document.referrer.length);

  // Manage the BACK button
  document.getElementById("backBtn").addEventListener('click', function onBackClick(evt) {
    evt.preventDefault();
    window.history.back();
  });

  // Manage the REFRESH Button
  document.getElementById("refreshBtn").addEventListener('click', function onRefreshClick(evt) {
    evt.preventDefault();
    window.location.reload(true);
  });

  document.getElementById("newBtn").addEventListener('click', function onNewClick(evt) {
    evt.preventDefault();
    window.location = '?view=report';
  });

  // Manage the DELETE button
  document.getElementById("deleteBtn").addEventListener('click', function onDeleteClick(evt) {
    if (!canEdit.Events) {
      enoperm();
      return;
    }

    evt.preventDefault();
    $j('#deleteConfirm').modal('show');
  });

  table.bootstrapTable('resetSearch');
  // The table is initially given a hidden style, so now that we are done rendering, show it
  table.show();
}

$j(document).ready(function() {
  initPage();
});
