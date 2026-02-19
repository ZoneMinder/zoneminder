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

// Manage the Add New Role button
function AddNewRole(el) {
  url = el.getAttribute('data-url');
  window.location.assign(url);
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
  const NewStorageBtn = $j('#NewStorageBtn');
  const NewServerBtn = $j('#NewServerBtn');
  const NewDatasetBtn = $j('#NewDatasetBtn');
  const NewModelBtn = $j('#NewModelBtn');
  const NewClassBtn = $j('#NewClassBtn');

  if ( canEdit.System ) enableStorageModal();
  if ( canEdit.System ) enableServerModal();
  if ( canEdit.System ) enableDatasetModal();
  if ( canEdit.System ) enableModelModal();
  if ( canEdit.System ) enableClassModal();

  NewStorageBtn.prop('disabled', !canEdit.System);
  NewServerBtn.prop('disabled', !canEdit.System);
  NewDatasetBtn.prop('disabled', !canEdit.System);
  NewModelBtn.prop('disabled', !canEdit.System);
  NewClassBtn.prop('disabled', !canEdit.System);

  // Dataset filter functionality for AI Classes tab
  $j('#datasetFilter').change(function() {
    var datasetId = $j(this).val();
    var $rows = $j('#contentTable tbody tr');
    if (datasetId === '') {
      $rows.show();
    } else {
      $rows.each(function() {
        var $row = $j(this);
        var rowDatasetId = $row.find('.classCol').data('dataset-id');
        if (rowDatasetId == datasetId) {
          $row.show();
        } else {
          $row.hide();
        }
      });
    }
  });

  $j('.bootstraptable').bootstrapTable({icons: icons}).show();
}

$j(document).ready(function() {
  initPage();
});
