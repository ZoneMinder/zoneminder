function setButtonStates(element) {
  const form = document.getElementById('filesForm');

  if (canEdit.System) {
    let disabled = true;

    const files = len=form.elements['files[]'];
    if (files) {
      for (let i=0, len=files.length; i<len; i++) {
        if (files[i].checked) {
          disabled = false;
          break;
        }
      }
    }
    form.elements['action'].disabled = disabled;
  } else {
    form.elements['action'].disabled = true;
  }
}

function initPage() {
  setButtonStates();
  document.querySelectorAll('input[name="files[]"]').forEach(function(el) {
    el.onclick=setButtonStates;
  });
}
$j(document).ready(initPage );
