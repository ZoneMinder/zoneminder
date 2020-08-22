function initPage() {
  // Manage the BACK button
  document.getElementById("backLnk").addEventListener("click", function onBackClick(evt) {
    evt.preventDefault();
    window.history.back();
  });
}

$j(document).ready(function() {
  initPage();
});
