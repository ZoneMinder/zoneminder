// Load the Server Modal HTML via Ajax call
function getServerModal(sid) {
  $j.getJSON(thisUrl + '?request=modal&modal=server&id=' + sid)
      .done(function(data) {
        insertModalHtml('ServerModal', data.html);
        $j('#ServerModal').modal('show');
      })
      .fail(logAjaxFail);
}

function enableServerModal() {
  $j(".serverCol").click(function(evt) {
    evt.preventDefault();
    const sid = $j(this).data('sid');
    getServerModal(sid);
  });
  $j('#NewServerBtn').click(function(evt) {
    evt.preventDefault();
    getServerModal(0);
  });
}

// Load the Storage Modal HTML via Ajax call
function getStorageModal(sid) {
  $j.getJSON(thisUrl + '?request=modal&modal=storage&id=' + sid)
      .done(function(data) {
        insertModalHtml('storageModal', data.html);
        $j('#storageModal').modal('show');
      })
      .fail(logAjaxFail);
}

function enableStorageModal() {
  $j(".storageCol").click(function(evt) {
    evt.preventDefault();
    var sid = $j(this).data('sid');
    getStorageModal(sid);
  });
  $j('#NewStorageBtn').click(function(evt) {
    evt.preventDefault();
    getStorageModal(0);
  });
}

// Manage the Add New User button
function AddNewUser(el) {
  url = el.getAttribute('data-url');
  window.location.assign(url);
}

// Load the AI Class Modal HTML via Ajax call
function getAIClassModal(cid) {
  $j.getJSON(thisUrl + '?request=modal&modal=ai_class&id=' + cid)
      .done(function(data) {
        insertModalHtml('AIClassModal', data.html);
        $j('#AIClassModal').modal('show');
      })
      .fail(logAjaxFail);
}

function enableAIClassModal() {
  $j(".aiClassCol").click(function(evt) {
    evt.preventDefault();
    const cid = $j(this).data('cid');
    getAIClassModal(cid);
  });
  $j('#NewAIClassBtn').click(function(evt) {
    evt.preventDefault();
    getAIClassModal(0);
  });
}

function initPage() {
  const NewStorageBtn = $j('#NewStorageBtn');
  const NewServerBtn = $j('#NewServerBtn');
  const NewAIClassBtn = $j('#NewAIClassBtn');

  if ( canEdit.System ) enableStorageModal();
  if ( canEdit.System ) enableServerModal();
  if ( canEdit.System ) enableAIClassModal();

  NewStorageBtn.prop('disabled', !canEdit.System);
  NewServerBtn.prop('disabled', !canEdit.System);
  NewAIClassBtn.prop('disabled', !canEdit.System);

  $j('.bootstraptable').bootstrapTable({icons: icons}).show();
}

$j(document).ready(function() {
  initPage();
});
