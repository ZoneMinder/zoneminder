var ProbeResults = {};
var table = null;

function ajaxRequest(params) {
  return probe(params);
}

function probe(params) {
  if (params.data && params.data.filter) {
    params.data.advsearch = params.data.filter;
    delete params.data.filter;
  }
  $j('#toolbar input, #toolbar select').each(function(index) {
    const el = $j(this);
    const name = el.attr('name');
    if (name) {
      params.data[name] = el.val();
      setCookie('addMonitors'+name, el.val(), 3600);
    }
  });
  if (auth_relay) params.data['auth_relay'] = auth_relay;

  $j.ajax({
    url: thisUrl + '?view=request&request=add_monitors&action=probe',
    data: params.data,
    timeout: 0,
    success: function(data) {
      if (data.result == 'Error') {
        alert(data.message);
        return;
      }

      if (data.Streams && data.Streams.length) {
        const rows = decorateStreams(data.Streams);
        // rearrange the result into what bootstrap-table expects
        params.success({total: rows.length, totalNotFiltered: rows.length, rows: rows});
      } else {
        params.success({total: 0, totalNotFiltered: 0, rows: []});
      }
    },
    error: function(jqXHR) {
      console.log("error", jqXHR);
      //logAjaxFail(jqXHR);
      //$j('#eventTable').bootstrapTable('refresh');
    }
  });
}

// Add the buttons/Thumbnail columns bootstrap-table expects and remember each
// stream by url so addMonitor() can find it. Shared by the network probe and
// the CSV import.
function decorateStreams(streams) {
  const rows = [];
  for (const i in streams) {
    const stream = streams[i];
    if (stream.Monitor) {
      stream.buttons = '<input type="button" value="Edit" data-on-click-this="addMonitor" data-url="'+stream.url+'"/>';
    } else if (stream.url && user.Monitors == 'Create') {
      stream.buttons = '<input type="button" value="Add" data-on-click-this="addMonitor" data-url="'+stream.url+'"/>';
    }
    if (ZM_WEB_LIST_THUMBS && stream.camera && stream.camera.mjpegstream) {
      stream.Thumbnail = '<img src="?view=image&proxy='+stream.camera.mjpegstream+'" width="'+ZM_WEB_LIST_THUMB_WIDTH+'"/>';
    }
    if (stream.url) {
      ProbeResults[stream.url] = stream;
    }
    rows.push(stream);
  } // end for each Stream
  return rows;
}

function onvif_probe() {
}

function addMonitor(btn) {
  url = btn.getAttribute('data-url');
  if (!url) {
    alert('No url in button');
    return;
  }
  if (!ProbeResults[url]) {
    alert("Monitor for url " + url + " not found in probe results." );
    return;
  }
  const Stream = ProbeResults[url];
  if (Stream.Monitor) {
    urlString = '?view=monitor&mid='+Stream.Monitor.Id;
  } else {
    const Monitor = Stream.camera.monitor;
    urlString = '?view=monitor&newMonitor[Path]='+url;
    keys = Object.keys( Monitor );
    for (i in Monitor) {
      if (!Monitor[i]) continue;
      if (Monitor[i] == 'null') {
        Monitor[i]='';
      }
      urlString += '&newMonitor['+i+']='+Monitor[i];
    }
  }
  window.location.assign(urlString);
}

function import_csv() {
  const form = $j('#importModalForm')[0];
  const formData = new FormData(form);

  // Use fetch, not $j.ajax: csrf-magic.js overrides XMLHttpRequest.send and does
  // `csrfMagicName + '=' + token + '&' + data`, which stringifies a FormData body
  // to "[object FormData]" and drops the uploaded file, so the backend sees no
  // file. fetch is not wrapped by csrf-magic. The __csrf_magic hidden input is
  // part of the form, so FormData(form) still carries the token as a real
  // multipart field.
  fetch(thisUrl+'?view=request&request=add_monitors&action=import', {
    method: 'POST',
    body: formData,
  })
      .then((resp) => resp.json())
      .then((data) => {
        if (data.result == 'Error') {
          alert(data.message);
          return;
        }
        $j('#ImportMonitorsModal').modal('hide');
        const rows = decorateStreams(data.Streams ? data.Streams : []);
        table.bootstrapTable('load', {total: rows.length, totalNotFiltered: rows.length, rows: rows});
      })
      .catch(logAjaxFail);
}

function importMonitors() {
}

function initPage() {
  table = $j('#AddMonitorsTable');
  // Init the bootstrap-table
  table.bootstrapTable({icons: icons,
    onPostBody: function() {
      dataOnClickThis();
    }
  });
  table.bootstrapTable('resetSearch');
  // The table is initially given a hidden style, so now that we are done rendering, show it
  table.show();
  $j('#url').on('input', probe);

  // Manage the EDIT button
  document.getElementById('importBtn').addEventListener('click', function onEditClick(evt) {
    evt.preventDefault();
    $j.ajax({
      method: 'GET',
      timeout: 0,
      url: thisUrl + '?request=modal&modal=add_monitors_import',
      success: function(data) {
        insertModalHtml('ImportMonitorsModal', data.html);
        $j('#ImportMonitorsModal').modal('show');
        // Manage the Save button
        $j('#importSubmitBtn').click(function(evt) {
          evt.preventDefault();
          import_csv();
        });
      },
      error: logAjaxFail
    });
  });
  $j('#toolbar input').on('change', function() {
    table.bootstrapTable('resetSearch');
    table.bootstrapTable('refresh');
  });
  $j('#toolbar select').on('change', function() {
    table.bootstrapTable('resetSearch');
    table.bootstrapTable('refresh');
  });
}

$j(document).ready(initPage);
