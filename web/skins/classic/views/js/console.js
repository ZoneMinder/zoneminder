"use strict";
const table = $j('#consoleTable');
var ajax = null;
var monitors = {}; // Store monitors by ID for function modal

// Update footer with dynamic totals
function updateFooter(footer) {
  // Target the footer within the bootstrap-table wrapper
  // Bootstrap-table may transform td to th and wrap content in divs
  var footerRow = $j('#consoleTable').closest('.bootstrap-table').find('tfoot tr');
  if (!footerRow.length) {
    footerRow = $j('#consoleTable tfoot tr');
  }
  
  // Helper function to update cell content (only updates th-inner div)
  function updateCell(selector, content) {
    var cell = footerRow.find(selector);
    if (cell.length) {
      // Only update the th-inner div if it exists
      var innerDiv = cell.find('.th-inner');
      if (innerDiv.length) {
        innerDiv.html(content);
      } else {
        cell.html(content);
      }
    }
  }
  
  // Update monitor count (in Id column if shown)
  updateCell('td.colId, th.colId', 'Total: ' + footer.monitor_count);
  
  // Update bandwidth/FPS (in Function column)
  updateCell('td.colFunction, th.colFunction', footer.bandwidth_fps);
  
  // Update event totals
  var eventPeriods = ['Total', 'Hour', 'Day', 'Week', 'Month', 'Archived'];
  var eventCells = footerRow.find('td.colEvents, th.colEvents');
  eventPeriods.forEach(function(period, index) {
    if (eventCells.length > index) {
      var cell = $j(eventCells[index]);
      // Only update the th-inner div if it exists
      var innerDiv = cell.find('.th-inner');
      var target = innerDiv.length ? innerDiv : cell;
      
      var link = target.find('a');
      if (link.length) {
        // Preserve the link but update the count
        var newHtml = footer[period + 'Events'] + '<br/><div class="small text-nowrap text-muted">' + 
                      footer[period + 'EventDiskSpace'] + '</div>';
        link.html(newHtml);
      } else {
        target.html(footer[period + 'Events'] + '<br/><div class="small text-nowrap text-muted">' + 
                    footer[period + 'EventDiskSpace'] + '</div>');
      }
    }
  });
  
  // Update zone count
  updateCell('td.colZones, th.colZones', footer.total_zones);
}

// Called by bootstrap-table to retrieve monitor data
function ajaxRequest(params) {
  if (ajax) ajax.abort();
  ajax = $j.ajax({
    method: 'POST',
    url: thisUrl + '?view=request&request=console&task=query',
    data: params.data,
    timeout: 0,
    success: function(data) {
      if (data.result == 'Error') {
        alert(data.message);
        return;
      }
      var rows = processRows(data.rows);
      // Store monitors for function modal using original ID
      rows.forEach(function(row) {
        monitors[row._id] = row;
      });
      
      // rearrange the result into what bootstrap-table expects
      params.success({total: data.total, totalNotFiltered: data.totalNotFiltered, rows: rows});
      
      // Update footer with totals from response after table is rendered
      if (data.footer) {
        updateFooter(data.footer);
      }
    },
    error: function(jqXHR) {
      if (jqXHR.statusText != 'abort') {
        console.log("error", jqXHR);
      }
    }
  });
}

function processRows(rows) {
  $j.each(rows, function(ndx, row) {
    var mid = row.Id;
    
    // Store original ID for later use
    row._id = mid;
    
    var stream_available = canView.Stream && (row.Type == 'WebSite' || (row.CaptureFPS && row.Capturing != 'None'));
    
    // Determine status classes
    var source_class = 'infoText';
    var source_class_reason = '';
    // FPS report interval: 60 seconds base + 30 seconds buffer for FPSReportInterval
    var fps_report_seconds = 90;
    
    if ((!row.Status || row.Status == 'NotRunning') && row.Type != 'WebSite') {
      source_class = 'errorText';
      source_class_reason = 'Not Running';
    } else if (!row.UpdatedOn || (new Date(row.UpdatedOn).getTime() < Date.now() - fps_report_seconds * 1000)) {
      source_class = 'errorText';
      source_class_reason = 'Offline';
    } else {
      if (row.CaptureFPS == '0.00') {
        source_class = 'errorText';
        source_class_reason = 'No capture FPS';
      } else if (!row.AnalysisFPS && row.Analysing != 'None') {
        source_class = 'warnText';
        source_class_reason = 'No analysis FPS';
      }
    }
    
    var dot_class = source_class;
    var dot_class_reason = source_class_reason;
    
    // Format Id column
    if (stream_available) {
      row.Id = '<a href="?view=watch&amp;mid=' + mid + '">' + mid + '</a>';
    } else {
      row.Id = mid;
    }
    
    // Thumbnail goes in its own column if enabled
    // (row.Thumbnail is already set from AJAX response)
    
    // Format Name column with status indicator, link, and groups (no thumbnail)
    var nameHtml = '<i class="material-icons ' + dot_class + '" title="' + dot_class_reason + '">lens</i> ';
    if (stream_available) {
      nameHtml += '<a href="?view=watch&amp;mid=' + mid + '">' + row.Name + '</a>';
    } else {
      nameHtml += row.Name;
    }
    
    // Add groups
    if (row.Groups) {
      nameHtml += '<br/><div class="small text-nowrap text-muted">' + row.Groups + '</div>';
    }
    
    row.Name = nameHtml;
    
    // Format Function column with status and FPS info
    var functionHtml = '';
    if (!row.UpdatedOn || (new Date(row.UpdatedOn).getTime() < Date.now() - fps_report_seconds * 1000)) {
      functionHtml = 'Offline<br/>';
    } else {
      functionHtml = 'Status: ' + row.Status + '<br/>';
      if (row.Analysing && row.Analysing != 'None') {
        functionHtml += 'Analysing: ' + row.Analysing + '<br/>';
      }
      if (row.Recording && row.Recording != 'None') {
        functionHtml += 'Recording: ' + row.Recording;
        if (row.ONVIF_Event_Listener) {
          functionHtml += ' Use ONVIF';
        }
        functionHtml += '<br/>';
      }
      functionHtml += '<br/><div class="small text-nowrap text-muted">';
      
      var fps_string = '';
      if (row.CaptureFPS) {
        fps_string = row.CaptureFPS;
      }
      if (row.AnalysisFPS && row.Analysing != 'None') {
        fps_string += '/' + row.AnalysisFPS;
      }
      if (fps_string) fps_string += ' fps';
      if (row.CaptureBandwidth) {
        fps_string += ' ' + row.CaptureBandwidth;
      }
      functionHtml += fps_string + '</div>';
    }
    row.Function = functionHtml;
    
    // Format Source column with link and dimensions
    var sourceHtml = '';
    if (canEdit.Monitors) {
      sourceHtml = '<a href="?view=monitor&amp;mid=' + mid + '"><span class="' + source_class + '">' + row.Source + '</span></a>';
    } else {
      sourceHtml = '<span class="' + source_class + '">' + row.Source + '</span>';
    }
    sourceHtml += '<br/>' + row.Width + 'x' + row.Height;
    row.Source = sourceHtml;
    
    // Format event count columns
    var eventPeriods = ['Total', 'Hour', 'Day', 'Week', 'Month', 'Archived'];
    eventPeriods.forEach(function(period) {
      if (canView.Events) {
        row[period + 'Events'] = '<a href="?view=' + ZM_WEB_EVENTS_VIEW + '&amp;MonitorId=' + mid + '">' + 
          row[period + 'Events'] + '</a><br/><div class="small text-nowrap text-muted">' + 
          row[period + 'EventDiskSpace'] + '</div>';
      } else {
        row[period + 'Events'] = row[period + 'Events'] + '<br/><div class="small text-nowrap text-muted">' + 
          row[period + 'EventDiskSpace'] + '</div>';
      }
    });
    
    // Format Zones column
    if (canView.Monitors) {
      row.ZoneCount = '<a href="?view=zones&amp;mid=' + mid + '">' + row.ZoneCount + '</a>';
    }
  });
  
  return rows;
}

function setButtonStates() {
  const selections = table.bootstrapTable('getSelections');
  const form = document.forms['monitorForm'];
  
  if (selections && selections.length > 0) {
    form.editBtn.disabled = false;
    form.deleteBtn.disabled = false;
    form.selectBtn.disabled = false;
    form.cloneBtn.disabled = false;
  } else {
    form.editBtn.disabled = true;
    form.deleteBtn.disabled = true;
    form.selectBtn.disabled = true;
    form.cloneBtn.disabled = true;
  }
}

function scanNetwork(element) {
  window.location.assign('?view=add_monitors');
}
function addMonitor(element) {
  if (user.Monitors == 'Create') {
    window.location.assign('?view=monitor');
  } else {
    alert('Need create monitors privilege');
  }
}

function cloneMonitor(element) {
  if (user.Monitors != 'Create') {
    alert('Need create monitors privilege');
    return;
  }
  const selections = table.bootstrapTable('getSelections');
  if (selections.length > 0) {
    var monitorId = selections[0]._id;
    window.location.assign('?view=monitor&dupId=' + monitorId);
  } else {
    alert('Please select a monitor to clone');
  }
}

function editMonitor(element) {
  const selections = table.bootstrapTable('getSelections');
  if (selections.length == 0) return;
  
  var monitorIds = selections.map(function(sel) {
    return sel._id;
  });
  
  if (monitorIds.length == 1) {
    window.location.assign('?view=monitor&mid=' + monitorIds[0]);
  } else if (monitorIds.length > 1) {
    window.location.assign('?view=monitors&' + (monitorIds.map(function(mid) {
      return 'mids[]=' + mid;
    }).join('&')));
  }
}

function deleteMonitor(element) {
  if (confirm('Deleting a monitor only marks it as deleted.  Events will age out. If you want them to be immediately removed, please delete them first.\nAre you sure you wish to delete?')) {
    const form = element.form;
    form.elements['action'].value = 'delete';
    
    // Get selected monitor IDs and add them to the form
    const selections = table.bootstrapTable('getSelections');
    selections.forEach(function(sel) {
      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'markMids[]';
      input.value = sel._id;
      form.appendChild(input);
    });
    
    form.submit();
  }
}

function selectMonitor(element) {
  const selections = table.bootstrapTable('getSelections');
  var url = thisUrl + '?view=console';
  
  selections.forEach(function(sel) {
    url += '&MonitorId[]=' + sel._id;
  });
  
  window.location.replace(url);
}

function reloadWindow() {
  // Use table refresh instead of full page reload
  if (table && table.length) {
    table.bootstrapTable('refresh');
  } else {
    window.location.replace(thisUrl);
  }
}

// Manage the the Function modal and its buttons
function manageFunctionModal(evt) {
  evt.preventDefault();

  if ( !canEdit.Events ) {
    enoperm();
    return;
  }

  if ( ! $j('#modalFunction').length ) {
    // Load the Function modal on page load
    $j.getJSON(thisUrl + '?request=modal&modal=function')
        .done(function(data) {
          insertModalHtml('modalFunction', data.html);
          // Manage the CANCEL modal buttons
          $j('.funcCancelBtn').click(function(evt) {
            evt.preventDefault();
            $j('#modalFunction').modal('hide');
          });
          // Manage the SAVE modal buttons
          $j('.funcSaveBtn').click(function(evt) {
            evt.preventDefault();
            $j('#function_form').submit();
          });

          manageFunctionModal(evt);
        })
        .fail(logAjaxFail);
    return;
  }

  var mid = evt.currentTarget.getAttribute('data-mid');
  monitor = monitors[mid];
  if ( !monitor ) {
    console.error("No monitor found for mid " + mid);
    return;
  }

  var function_form = document.getElementById('function_form');
  if ( !function_form ) {
    console.error("Unable to find form with id function_form");
    return;
  }
  function_form.elements['newFunction'].onchange=function() {
    $j('#function_help div').hide();
    $j('#'+this.value+'Help').show();
    if ( this.value == 'Monitor' || this.value == 'None' ) {
      $j('#FunctionAnalysisEnabled').hide();
    } else {
      $j('#FunctionAnalysisEnabled').show();
    }
    if ( this.value == 'Record' || this.value == 'Nodect' ) {
      $j('#FunctionDecodingEnabled').show();
    } else {
      $j('#FunctionDecodingEnabled').hide();
    }
  };
  function_form.elements['newFunction'].value = monitor.Function;
  function_form.elements['newFunction'].onchange();

  function_form.elements['newEnabled'].checked = monitor.Enabled == '1';
  function_form.elements['newDecodingEnabled'].checked = monitor.DecodingEnabled == '1';
  function_form.elements['mid'].value = mid;
  document.getElementById('function_monitor_name').innerHTML = monitor.Name;

  $j('#modalFunction').modal('show');
} // end function manageFunctionModal

function initPage() {
  // Init the bootstrap-table
  table.bootstrapTable({icons: icons});
  
  // Enable or disable buttons based on current selection and user rights
  table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table',
    function() {
      const selections = table.bootstrapTable('getSelections');
      const form = document.forms['monitorForm'];
      
      if (selections.length > 0) {
        form.editBtn.disabled = false;
        form.deleteBtn.disabled = false;
        form.selectBtn.disabled = false;
        form.cloneBtn.disabled = false;
      } else {
        form.editBtn.disabled = true;
        form.deleteBtn.disabled = true;
        form.selectBtn.disabled = true;
        form.cloneBtn.disabled = true;
      }
    }
  );
  
  // Setup automatic refresh with table refresh instead of page reload
  if (consoleRefreshTimeout > 0) {
    setInterval(reloadWindow, consoleRefreshTimeout);
  }
  
  if ( showDonatePopup ) {
    $j.getJSON(thisUrl + '?request=modal&modal=donate')
        .done(function(data) {
          insertModalHtml('donate', data.html);
          $j('#donate').modal('show');
          // Manage the Apply button
          $j('#donateApplyBtn').click(function(evt) {
            evt.preventDefault();
            $j('#donateForm').submit();
          });
        })
        .fail(logAjaxFail);
  }

  // Setup the thumbnail video animation after table loads
  table.on('post-body.bs.table', function() {
    if (!isMobile()) initThumbAnimation();
    $j('.functionLnk').click(manageFunctionModal);
  });

  // Makes table sortable - disabled by default, enabled by Sort button
  // Note: This may need adjustment for bootstrap-table compatibility
  $j('#consoleTableBody').sortable({
    disabled: true,
    update: applySort,
    axis: 'Y'} );
  
  // Make the table visible after initialization
  table.show();
} // end function initPage

function sortMonitors(button) {
  if (button.classList.contains('btn-success')) {
    $j( "#consoleTableBody" ).sortable('disable');
  } else {
    $j( "#consoleTableBody" ).sortable('enable');
  }
  button.classList.toggle('btn-success');
}

function applySort(event, ui) {
  var monitor_ids = $j(this).sortable('toArray');
  var data = {monitor_ids: monitor_ids, action: 'sort'};

  $j.post(thisUrl + '?request=console', data)
      .fail(logAjaxFail);
} // end function applySort(event,ui)

$j(document).ready(initPage);
