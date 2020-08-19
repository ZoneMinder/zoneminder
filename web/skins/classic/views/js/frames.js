function thumbnail_onmouseover(event) {
  var img = event.target;
  img.src = '';
  img.src = img.getAttribute('full_img_src');
}

function thumbnail_onmouseout(event) {
  var img = event.target;
  img.src = '';
  img.src = img.getAttribute('img_src');
}

function initThumbAnimation() {
  $j('.colThumbnail img').each(function() {
    this.addEventListener('mouseover', thumbnail_onmouseover, false);
    this.addEventListener('mouseout', thumbnail_onmouseout, false);
  });
}

function initPage() {
  var backBtn = $j('#backBtn');
  var table = $j('#framesTable');

  // Define the icons used in the bootstrap-table top-right toolbar
  var icons = {
    paginationSwitchDown: 'fa-caret-square-o-down',
    paginationSwitchUp: 'fa-caret-square-o-up',
    refresh: 'fa-sync',
    toggleOff: 'fa-toggle-off',
    toggleOn: 'fa-toggle-on',
    columns: 'fa-th-list',
    fullscreen: 'fa-arrows-alt',
    detailOpen: 'fa-plus',
    detailClose: 'fa-minus'
  };

  // Init the bootstrap-table
  table.bootstrapTable('destroy').bootstrapTable({icons: icons});

  // Disable the back button if there is nothing to go back to
  backBtn.prop('disabled', !document.referrer.length);

  // Setup the thumbnail animation
  initThumbAnimation();

  // Manage the BACK button
  document.getElementById("backBtn").addEventListener("click", function onBackClick(evt) {
    evt.preventDefault();
    window.history.back();
  });

  // Manage the REFRESH Button
  document.getElementById("refreshBtn").addEventListener("click", function onRefreshClick(evt) {
    evt.preventDefault();
    window.location.reload(true);
  });
}

$j(document).ready(function() {
  initPage();
});
