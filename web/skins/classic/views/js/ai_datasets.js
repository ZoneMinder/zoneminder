function validateForm(form) {
  var errors = [];
  if ( !form.elements['newDataset[Name]'].value ) {
    errors[errors.length] = 'You must supply a name';
  }
  if ( !form.elements['newDataset[NumClasses]'].value || form.elements['newDataset[NumClasses]'].value < 0 ) {
    errors[errors.length] = 'You must supply a valid number of classes';
  }
  if ( errors.length ) {
    alert(errors.join("\n"));
    return false;
  }
  return true;
}

// Load the Dataset Modal HTML via Ajax call
function getDatasetModal(did) {
  $j.getJSON(thisUrl + '?request=modal&modal=ai_dataset&id=' + did)
      .done(function(data) {
        insertModalHtml('DatasetModal', data.html);
        $j('#DatasetModal').modal('show');
      })
      .fail(logAjaxFail);
}

function enableDatasetModal() {
  $j(".datasetCol").click(function(evt) {
    evt.preventDefault();
    const did = $j(this).data('did');
    getDatasetModal(did);
  });
  $j('#NewDatasetBtn').click(function(evt) {
    evt.preventDefault();
    getDatasetModal(0);
  });
}

function initPage() {
  const NewDatasetBtn = $j('#NewDatasetBtn');
  if ( canEdit.System ) enableDatasetModal();
  NewDatasetBtn.prop('disabled', !canEdit.System);
}

$j(document).ready(function() {
  initPage();
});
