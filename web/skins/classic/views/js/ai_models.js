function validateForm(form) {
  var errors = [];
  if ( !form.elements['newModel[Name]'].value ) {
    errors[errors.length] = 'You must supply a name';
  }
  if ( errors.length ) {
    alert(errors.join("\n"));
    return false;
  }
  return true;
}

// Load the Model Modal HTML via Ajax call
function getModelModal(mid) {
  $j.getJSON(thisUrl + '?request=modal&modal=ai_model&id=' + mid)
      .done(function(data) {
        insertModalHtml('ModelModal', data.html);
        $j('#ModelModal').modal('show');
      })
      .fail(logAjaxFail);
}

function enableModelModal() {
  $j(".modelCol").click(function(evt) {
    evt.preventDefault();
    const mid = $j(this).data('mid');
    getModelModal(mid);
  });
  $j('#NewModelBtn').click(function(evt) {
    evt.preventDefault();
    getModelModal(0);
  });
}

function initPage() {
  const NewModelBtn = $j('#NewModelBtn');
  if ( canEdit.System ) enableModelModal();
  NewModelBtn.prop('disabled', !canEdit.System);
}

$j(document).ready(function() {
  initPage();
});
