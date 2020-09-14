// Load the Storage Modal HTML via Ajax call
function getStorageModal(sid) {
  $j.getJSON(thisUrl + '?request=modal&modal=storage&id=' + sid)
      .done(function(data) {
        if ( $j('#storageModal').length ) {
          $j('#storageModal').replaceWith(data.html);
        } else {
          $j("body").append(data.html);
        }
        $j('#storageModal').modal('show');
        // Manage the Save button
        $j('#storageSubmitBtn').click(function(evt) {
          evt.preventDefault();
          $j('#storageModalForm').submit();
        });
      })
      .fail(function(jqxhr, textStatus, error) {
        console.log("Request Failed: " + textStatus + ", " + error);
        console.log("Response Text: " + jqxhr.responseText);
      });
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

function initPage() {
  var NewStorageBtn = $j('#NewStorageBtn');

  if ( canEditSystem ) enableStorageModal();

  NewStorageBtn.prop('disabled', !canEditSystem);
}

$j(document).ready(function() {
  initPage();
});
