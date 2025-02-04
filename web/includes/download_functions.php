<?php
//
// ZoneMinder web download function library
// Copyright (C) 2023 ZoneMinder Inc
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

function exportEvents(
  $eids,
  $export_root,
  $exportFormat,
  $exportCompressed,
  $exportStructure = false
) {

  if (!(canView('Events') or canView('Snapshots'))) {
    ZM\Error('You do not have permission to view events.');
    return false;
  } else if (empty($eids)) {
    ZM\Error('Attempt to export an empty list of events.');
    return false;
  }

  if (!($exportFormat == 'tar' or $exportFormat == 'zip')) {
    ZM\Error("None or invalid exportFormat specified $exportFormat.");
    return false;
  }

  # Ensure that we are going to be able to do this.
  if (!(@mkdir(ZM_DIR_EXPORTS) or file_exists(ZM_DIR_EXPORTS))) {
    ZM\Fatal('Can\'t create exports dir at \''.ZM_DIR_EXPORTS.'\'');
  }
  chmod(ZM_DIR_EXPORTS, 0700);
  $export_dir = ZM_DIR_EXPORTS.'/'.$export_root;

  # Ensure that we are going to be able to do this.
  if (!(@mkdir($export_dir) or file_exists($export_dir))) {
    ZM\Error("Can't create exports dir at '$export_dir'");
    return false;
  }
  chmod($export_dir, 0700);
  if (!chdir($export_dir)) {
    ZM\Error("Can't chdir to $export_dir");
    return;
  }

  if (!is_array($eids)) {
    $eids = array($eids);
  }
  $events_by_monitor_id = [];
  foreach ($eids as $eid) {
    $event = new ZM\Event($eid);
    if (!$event->canView()) {
      global $user;
      ZM\Warning('User '.($user?$user['Username']:'').' cannot view event '.$event->Id());
      continue;
    }
    if (!isset($events_by_monitor_id[$event->MonitorId()])) $events_by_monitor_id[$event->MonitorId()] = [];
    $events_by_monitor_id[$event->MonitorId()][] = $event;

    if (!$event->DefaultVideo()) $event->GenerateVideo();
  }

  $exportFileList = [];

  foreach (array_keys($events_by_monitor_id) as $mid) {
    $monitor = ZM\Monitor::find_one(['Id'=>$mid]);
    if (!$monitor) {
      ZM\Error("No monitor found for id $mid");
      continue;
    }

    usort($events_by_monitor_id[$mid], function($a, $b) {
        return strtotime($a->StartDateTime) <=> strtotime($b->StartDateTime);
    });

    $eventFileList = '';
    $minTimeSecs = -1;
    $minTime = '';
    $maxTimeSecs = -1;
    $maxTime = '';
    foreach ($events_by_monitor_id[$mid] as $event) {
      if ($minTimeSecs == -1 or $minTimeSecs > $event->StartDateTimeSecs()) {
        $minTimeSecs = $event->StartDateTimeSecs();
        $minTime = $event->StartDateTime();
      }
      if ($maxTimeSecs == -1 or $maxTimeSecs < $event->StartDateTimeSecs()) {
        $maxTimeSecs = $event->StartDateTimeSecs();
        $maxTime = $event->StartDateTime();
      }
      $eventFileList .= 'file \''.$event->Path().'/'.$event->DefaultVideo().'\''.PHP_EOL;
    }

    $mergedFileName = $monitor->Name().' '.$minTime.' to '.$maxTime.'.mp4';
    if (($fp = fopen('event_files.txt', 'w'))) {
      fwrite($fp, $eventFileList);
      fclose($fp);
    } else {
      ZM\Error("Can't open event images export file 'event_files.txt'");
    }
    $cmd = ZM_PATH_FFMPEG.' -f concat -safe 0 -i event_files.txt -c copy \''.$export_dir.'/'.$mergedFileName. '\' 2>&1';
    exec($cmd, $output, $return);
    ZM\Debug($cmd.' return code: '.$return.' output: '.print_r($output,true));
    $exportFileList[] = $mergedFileName;
    unlink('event_files.txt');
  } # end foreach monitor

  $export_listFile = 'FileList.txt';
  $listFile = $export_dir.'/'.$export_listFile;
  if (!($fp = fopen($listFile, 'w'))) {
    ZM\Error("Can't open event export list file '$listFile'");
    return false;
  }
  foreach ($exportFileList as $exportFile) {
    $exportFile = $export_root.'/'.$exportFile;
    fwrite($fp, $exportFile.PHP_EOL);
  }
  fwrite($fp, $export_listFile.PHP_EOL);
  fclose($fp);

  chdir(ZM_DIR_EXPORTS);
  $archive = '';
  if ($exportFormat == 'tar') {
    $archive = $export_root.'.tar';
    $version = @shell_exec('tar --version');
    ZM\Debug("Version $version");

    $command = 'tar --create --dereference';
    if ($exportCompressed) {
      $archive .= '.gz';
      $command .= ' --gzip';
      $exportFormat .= '.gz';
    }
    if ($exportStructure == 'flat') {
      if (preg_match('/BSD/i', $version)) {
        $command .= ' -s \'#^.*/##\'';
      } else {
        $command .= ' --xform=\'s#^.+/##x\'';
      }
    }
    $archive_path = ZM_DIR_EXPORTS.'/'.$archive;
    $command .= ' --file='.escapeshellarg($archive_path);
  } else if ($exportFormat == 'zip') {
    $archive = $export_root.'.zip';
    $archive_path = ZM_DIR_EXPORTS.'/'.$archive;
    $command = 'zip -r ';
    $command .= ($exportStructure == 'flat' ? ' -j ' : '').escapeshellarg($archive_path);
    $command .= $exportCompressed ? ' -9' : ' -0';
  } // if $exportFormat

  @unlink($archive_path); # delete it if it exists already
  $command .= ' '.$export_root.'/';
  ZM\Debug($command);
  exec($command, $output, $status);
  if ($status) {
    ZM\Error("Command '$command' returned with status $status");
    if (isset($output[0])) {
      ZM\Error('First line of output is \''.$output[0].'\'');
    }
    return false;
  }

  // clean up temporary files
  if (!empty($html_eventMaster)) {
    unlink($monitorPath.'/'.$html_eventMaster);
  }

  return '?view=archive&type='.$exportFormat.'&file='.$archive;
} // end function exportEvents
