function validateForm(form) {
  var errors = [];
  if ( !form.elements['newClass[DatasetId]'].value ) {
    errors[errors.length] = 'You must select a dataset';
  }
  if ( !form.elements['newClass[ClassName]'].value ) {
    errors[errors.length] = 'You must supply a class name';
  }
  if ( !form.elements['newClass[ClassIndex]'].value || form.elements['newClass[ClassIndex]'].value < 0 ) {
    errors[errors.length] = 'You must supply a valid class index';
  }
  if ( errors.length ) {
    alert(errors.join("\n"));
    return false;
  }
  return true;
}

// Load the Class Modal HTML via Ajax call
function getClassModal(cid) {
  $j.getJSON(thisUrl + '?request=modal&modal=ai_class&id=' + cid)
      .done(function(data) {
        insertModalHtml('ClassModal', data.html);
        $j('#ClassModal').modal('show');
      })
      .fail(logAjaxFail);
}

function enableClassModal() {
  $j(".classCol").click(function(evt) {
    evt.preventDefault();
    const cid = $j(this).data('cid');
    getClassModal(cid);
  });
  $j('#NewClassBtn').click(function(evt) {
    evt.preventDefault();
    getClassModal(0);
  });
}

function initPage() {
  const NewClassBtn = $j('#NewClassBtn');
  if ( canEdit.System ) enableClassModal();
  NewClassBtn.prop('disabled', !canEdit.System);

  // Dataset filter functionality
  $j('#datasetFilter').change(function() {
    var datasetId = $j(this).val();
    if (datasetId === '') {
      $j('#contentTable tbody tr').show();
    } else {
      $j('#contentTable tbody tr').hide();
      $j('#contentTable tbody tr').find('.classCol[data-dataset-id="' + datasetId + '"]').closest('tr').show();
    }
  });
}

$j(document).ready(function() {
  initPage();
});
