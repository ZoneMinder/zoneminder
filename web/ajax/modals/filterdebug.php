<div class="modal fade" id="filterdebugModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
     <div class="modal-header">
     <h5 class="modal-title"><?php echo translate('FilterDebug') ?></h5>
       <button type="button" class="close" data-dismiss="modal" aria-label="Close">
         <span aria-hidden="true">&times;</span>
       </button>
     </div>
     <div class="modal-body">
<?php
  require_once('includes/Filter.php');
  $fid = validInt($_REQUEST['fid']);

  $filter = null;
  if ($fid) {
    $filter = new ZM\Filter($fid);
    if (!$filter->Id()) {
      echo '<div class="error">Filter not found for id '.$_REQUEST['fid'].'</div>';
    }
  } else {
   $filter = new ZM\Filter();
   if ( isset($_REQUEST['filter'])) {
     $filter->set($_REQUEST['filter']);
   } else {
     echo '<div class="error">No filter id or contents specified.</div>';
   }
  }
?>
       <form name="contentForm" id="filterdebugForm" method="post" action="?">
<?php 
            // We have to manually insert the csrf key into the form when using a modal generated via ajax call
            echo getCSRFinputHTML();
?>
          <p><label>SQL</label>
<?php
  $sql = 'SELECT E.*,M.Name AS MonitorName,M.DefaultScale FROM Monitors AS M INNER JOIN Events AS E ON (M.Id = E.MonitorId)
    WHERE
';
  $sql .= $filter->sql();
  $sql .= $filter->sort_field() ? ' ORDER BY '.$filter->sort_field(). ' ' .($filter->sort_asc() ? 'ASC' : 'DESC') : '';
  $sql .= $filter->limit() ? ' LIMIT '.$filter->limit() : '';
  #$sql .= $filter->skip_locked() ? ' SKIP LOCKED' : '';


  echo preg_replace('/\n/', '<br/>', $sql);
?></p>
<p><label>MySQL Explanation</label>
<?php
  echo '
<table>
  <thead>
    <tr>
      <th>Select Type</th>
      <th>Table</th>
      <th>Partitions</th>
      <th>Type</th>
      <th>Possible Keys</th>
      <th>Key</th>
      <th>Key Length</th>
      <th>Ref</th>
      <th>Rows</th>
      <th>Filtered</th>
      <th>Extra</th>
    </tr>
  </thead>
  <tbody>
';
  $result = dbFetchAll('EXPLAIN '.$sql);
  foreach ($result as $row) {
    echo '<tr>'.PHP_EOL.
  '<td>'.$row['select_type'].'</td>'.
  '<td>'.$row['table'].'</td>'.
  '<td>'.$row['partitions'].'</td>'.
  '<td>'.$row['type'].'</td>'.
  '<td>'.$row['possible_keys'].'</td>'.
  '<td>'.$row['key'].'</td>'.
  '<td>'.$row['key_len'].'</td>'.
  '<td>'.$row['ref'].'</td>'.
  '<td>'.$row['rows'].'</td>'.
  '<td>'.$row['filtered'].'</td>'.
  '<td>'.$row['Extra'].'</td>';
    echo '</tr>';
  }
    echo '</tbody></table>';

?>
</p>
         <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('Close')?> </button>
        </div>
      </form>
    </div>
  </div>
</div>
