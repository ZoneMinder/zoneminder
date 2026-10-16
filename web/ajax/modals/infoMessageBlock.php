<?php

$message = isset($_REQUEST['message']) ? htmlspecialchars(strip_tags($_REQUEST['message']), ENT_NOQUOTES, 'UTF-8') : '';
$title = isset($_REQUEST['title']) ? htmlspecialchars(strip_tags($_REQUEST['title']), ENT_NOQUOTES, 'UTF-8') : '';

?>
<div id="modalInfoMessageBlock" class="modal fade" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
<?php if ($title !== '') echo '
      <div class="modal-header">
        <h5 class="modal-title">' . translate($title) . '</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>';
?>
      <div class="modal-body">
        <p><?php echo str_replace('~~', '</br>', translate($message)) ?></p>
      </div>
      <div class="modal-footer">
        <button id="btnModalInfoMessageBlock" type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('Close') ?></button>
      </div>
    </div>
  </div>
</div>