function setButtonStates() {
  const form = document.getElementById('filesForm');
  let disabled = true;

  if (canEdit.System) {
    const files = form.elements['files[]'];
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
  const form = document.getElementById('filesForm');
  let input = form.querySelector('input[name="action"]');
  if (!input) {
    input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'action';
    form.appendChild(input);
  }
  input.value = 'delete';
  getDelConfirmModal('ConfirmDeleteFiles', 'Delete', 'filesForm');
}

function initPage() {
  setButtonStates();
  document.querySelectorAll('input[name="files[]"]').forEach(function(el) {
    el.onclick=setButtonStates;
  });
}
$j(document).ready(initPage );
