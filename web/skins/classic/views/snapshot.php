<?php
//
// ZoneMinder web snapshot view file, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

if (!canView('Snapshots')) {
  $view = 'error';
  return;
} else if (!ZM_FEATURES_SNAPSHOTS) {
  $view = getHomeView();
  return;
}

require_once('includes/Event.php');
require_once('includes/Filter.php');
require_once('includes/Snapshot.php');

$id = isset($_REQUEST['id']) ? validInt($_REQUEST['id']) : null;
$snapshot = new ZM\Snapshot($id);

$monitors = array();
if ( count($user->unviewableMonitorIds())) {
  $monitor_ids = $user->viewableMonitorIds();
}
xhtmlHeaders(__FILE__, translate('Snapshot').' '.$snapshot->Id());
getBodyTopHTML();
echo getNavBarHTML();
?>
  <div id="page">
    <div id="content">
<?php 
if ( !$snapshot->Id() ) {
  echo '<div class="error">Snapshot was not found.</div>';
}
?>
<!-- BEGIN HEADER -->
  <form action="?view=snapshot&id=<?php echo $snapshot->Id() ?>" method="post">
    <div class="d-flex flex-row justify-content-between px-3 py-1">
      <div id="toolbar">
        <button type="button" id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
        <button type="button" id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>" ><i class="fa fa-refresh"></i></button>
<?php if ( $snapshot->Id() ) { ?>
<!--
        <button id="editBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Edit') ?>" disabled><i class="fa fa-pencil"></i></button>
-->
        <button type="submit" name="action" value="save" id="saveBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Save') ?>"><i class="fa fa-save"></i></button>
        <button type="button" id="exportBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Export') ?>"><i class="fa fa-external-link"></i></button>
        <button type="button" id="downloadBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Download') ?>" disabled><i class="fa fa-download"></i></button>
        <button type="button" id="deleteBtn" class="btn btn-danger" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Delete') ?>"><i class="fa fa-trash"></i></button>
<?php } // end if snapshot->Id ?>
      </div>
      
      <h2><?php echo translate('Snapshot').' '.$snapshot->Id() ?></h2>
    </div>
    <div class="d-flex flex-row justify-content-between py-1" id="snapshot">
      <!--
      <div class="form-group"><label><?php echo translate('Created By') ?></label>
      -->
      <div class="form-group CreatedOn"><label><?php echo translate('Created On') ?></label>
        <?php echo $snapshot->CreatedOn() ?>
      </div>
      <div class="form-group Name"><label><?php echo translate('Reference') ?></label>
        <input type="text" name="snapshot[Name]" value="<?php echo validHtmlStr($snapshot->Name()); ?>"/>
      </div>
      <div class="form-group Description"><label>
        <?php echo translate('Notes') ?></label>
        <textarea name="snapshot[Description]"><?php echo validHtmlStr($snapshot->Description()); ?></textarea>
      </div>
    </div>
  </form>
<?php if ( $snapshot->Id() ) { ?>
<!-- BEGIN VIDEO CONTENT ROW -->
    <div id="video" class="row justify-content-center">
<?php
    $events = $snapshot->Events();
    $width = 100 / ( count($events) < 2 ? 1 : ( ( count($events) < 4 ) ? count($events) : 4 ) )-1;
    foreach ( $snapshot->Events() as $event ) {
      $imgSrc = $event->getThumbnailSrc(array(), '&amp;');
      echo '<img src="?view=image&eid='.$event->Id().'&fid=snapshot" width="'.$width.'%"/>';
    }
?>
    </div><!--content-->
<?php } // end if snapshot->Id() ?>
  <h2 id="downloadProgress" class="<?php
            if ( isset($_REQUEST['generated']) ) {
              if ( $_REQUEST['generated'] )
                echo 'infoText';
              else
                echo 'errorText';
            } else {
              echo 'hidden warnText';
            }
        ?>">
    <span id="downloadProgressText">
      <?php
        if ( isset($_REQUEST['generated']) ) {
          if ( $_REQUEST['generated'] )
            echo translate('Download Succeeded');
          else
            echo translate('Download Failed');
        }
    ?></span>
    <span id="downloadProgressTicker"></span>
  </h2>
  </div>
</div><!--page-->
<?php xhtmlFooter() ?>
