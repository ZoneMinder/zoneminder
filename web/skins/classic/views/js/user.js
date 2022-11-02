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

function initPage() {
  $j('#contentForm').submit(function(event) {
    if ( validateForm(this) ) {
      $j('#contentButtons').hide();
      return true;
    } else {
      return false;
    };
  });

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
