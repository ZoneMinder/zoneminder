var newDeviceBtn = $j('#newDeviceBtn');

function switchDeviceOn( element, key ) {
  var form = element.form;
  form.view.value = currentView;
  form.action.value = 'device';
  form.command.value = 'on';
  form.key.value = key;
  form.submit();
}

function switchDeviceOff( element, key ) {
  var form = element.form;
  form.view.value = currentView;
  form.action.value = 'device';
  form.command.value = 'off';
  form.key.value = key;
  form.submit();
}

function deleteDevice( element ) {
  var form = element.form;
  form.view.value = currentView;
  form.action.value = 'delete';
  form.submit();
}

function configureButtons( element, name ) {
  var form = element.form;
  var checked = false;
  for (var i = 0; i < form.elements.length; i++) {
    if ( form.elements[i].name.indexOf(name) == 0) {
      if ( form.elements[i].checked ) {
        checked = true;
        break;
      }
    }
  }
  form.deleteBtn.disabled = !checked;
}

// Load the Device Modal HTML via Ajax call
function getDeviceModal(did) {
  $j.getJSON(thisUrl + '?request=modal&modal=device&did=' + did)
      .done(function(data) {
        if ( $j('#deviceModal').length ) {
          $j('#deviceModal').replaceWith(data.html);
        } else {
          $j("body").append(data.html);
        }
        $j('#deviceModal').modal('show');
        // Manage the Save button
        $j('#deviceSaveBtn').click(function(evt) {
          evt.preventDefault();
          $j('#deviceModalForm').submit();
        });
      })
      .fail(logAjaxFail);
}

function enableDeviceModal() {
  $j(".deviceCol").click(function(evt) {
    evt.preventDefault();
    var did = $j(this).data('did');
    getDeviceModal(did);
  });
  newDeviceBtn.click(function(evt) {
    evt.preventDefault();
    getDeviceModal(0);
  });
}

function initPage() {
  if ( canEditDevice ) enableDeviceModal();

  newDeviceBtn.prop('disabled', !canEditDevice);

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
}

$j(document).ready(initPage);
