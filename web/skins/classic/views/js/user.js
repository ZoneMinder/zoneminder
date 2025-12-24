function validateForm( form, newUser ) {
  var errors = new Array();
  if ( !form.elements['user[Username]'].value ) {
    errors[errors.length] = "You must supply a username";
  }
  if ( form.elements['user[Password]'].value ) {
    if ( !form.conf_password.value ) {
      errors[errors.length] = "You must confirm the password";
    } else if ( form.elements['user[Password]'].value != form.conf_password.value ) {
      errors[errors.length] = "The new and confirm passwords are different";
    }
  } else if ( newUser ) {
    errors[errors.length] = "You must supply a password";
  }
  if ( errors.length ) {
    alert(errors.join("\n"));
    return false;
  }
  return true;
}

// Called by bootstrap-table to retrieve monitor data
var ajax = null;
function ajaxRequest(params) {
  if (params.data && params.data.filter) {
    params.data.advsearch = params.data.filter;
    delete params.data.filter;
  }
  
  // Add user ID to the request
  params.data.uid = userId;
  
  if (ajax) ajax.abort();
  ajax = $j.ajax({
    method: 'POST',
    url: '?view=request&request=user_monitors&task=query',
    data: params.data,
    timeout: 0,
    success: function(data) {
      if (data.result == 'Error') {
        alert(data.message);
        return;
      }
      var rows = processRows(data.rows);
      // rearrange the result into what bootstrap-table expects
      params.success({total: data.total, totalNotFiltered: data.totalNotFiltered, rows: rows});
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
    var monitorId = row.Id;
    
    // Build permission radio buttons
    var permissionHtml = '';
    for (var value in permissionOptions) {
      var label = permissionOptions[value];
      var checked = (value == row.Permission) ? ' checked="checked"' : '';
      
      permissionHtml += '<div class="form-check form-check-inline">';
      permissionHtml += '<label class="form-check-label radio-inline" for="monitor_permission[' + monitorId + ']_' + value + '">';
      permissionHtml += '<input class="form-check-input" type="radio" name="monitor_permission[' + monitorId + ']" value="' + value + '" id="monitor_permission[' + monitorId + ']_' + value + '"' + checked + ' data-on-change="updateEffectivePermissions" />';
      permissionHtml += label + '</label></div>';
    }
    
    row.Permission = permissionHtml;
    
    // Store effective permission with an ID for updates
    row.EffectivePermission = '<span id="effective_permission' + monitorId + '">' + row.EffectivePermission + '</span>';
  });
  
  return rows;
}

function initPage() {
  $j('#contentForm').submit(function(event) {
    if ( validateForm(this) ) {
      $j('#contentButtons').hide();
      return true;
    } else {
      return false;
    };
  });

  // Initialize bootstrap-table event handlers
  const table = $j('#monitorPermissionsTable');
  
  // Update event handlers after bootstrap-table renders rows
  table.on('post-body.bs.table', function(data) {
    // Re-bind data-on-change handlers for the newly rendered radio buttons
    if (typeof dataOnChange === 'function') {
      dataOnChange();
    }
  });

  // Initialize bootstrap-table
  table.bootstrapTable({icons: icons});
  
  // Show the table after initialization
  table.show();

  // Manage the BACK button
  document.getElementById("backBtn").addEventListener("click", function onBackClick(evt) {
    evt.preventDefault();
    window.history.back();
  });

  // Disable the back button if there is nothing to go back to
  $j('#backBtn').prop('disabled', !document.referrer.length);

  // Manage the REFRESH Button
  document.getElementById("refreshBtn").addEventListener("click", function onRefreshClick(evt) {
    evt.preventDefault();
    window.location.reload(true);
  });
} // end function initPage

function updateEffectivePermissions() {
  console.log("updateEffectivePermissions");
  const form = document.getElementById('contentForm');
  if (!form) {
    console.error('No form found for contentForm');
    return;
  }

  for (let monitor_i=0, monitor_len=monitors.length; monitor_i< monitor_len; monitor_i++) {
    const monitor = monitors[monitor_i];
    const perm = getEffectivePermission(monitor);
    $j('#effective_permission'+monitor.id).html(perm);
  } // end foreach monitor
} // end funtion updateEffectivePermissions()

function getEffectivePermission(monitor) {
  {
    const perm = $j('input[name="monitor_permission\['+monitor.id+'\]"]:checked').val();
    if (perm != 'Inherit') return perm;
  }

  const gp_permissions = [];
  for (let group_i=0, group_len=groups.length; group_i < group_len; group_i++) {
    const group = groups[group_i];

    if (group.monitor_ids.includes(monitor.id)) {
      const perm = $j('input[name="group_permission\['+group.id+'\]"]:checked').val();
      gp_permissions[perm] = perm;
    }
  }
  if (gp_permissions['None']) return 'None';
  if (gp_permissions['View']) return 'View';
  if (gp_permissions['Edit']) return 'Edit';
  return $j('#user\\[Monitors\\]').val();
}

window.addEventListener('DOMContentLoaded', initPage);
