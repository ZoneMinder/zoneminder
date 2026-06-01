function setButtonStates() {
  const form = document.getElementById('filesForm');
  let disabled = true;

  if (canEdit.System) {
    const files = len=form.elements['files[]'];
    if (files) {
      for (let i=0, len=files.length; i<len; i++) {
        if (files[i].checked) {
          disabled = false;
          break;
        }
      }
    }
  }
  document.getElementById('btnDeleteFiles').disabled = disabled;
}

function deleteFiles() {
  const input = document.createElement('input');
  input.type = 'hidden';
  input.name = 'action';
  input.value = 'delete';
  document.getElementById('filesForm').appendChild(input);
  getDelConfirmModal('ConfirmDeleteFiles', 'Delete', 'filesForm');
}

function initPage() {
  setButtonStates();
  document.querySelectorAll('input[name="files[]"]').forEach(function(el) {
    el.onclick=setButtonStates;
  });
}
$j(document).ready(initPage );
