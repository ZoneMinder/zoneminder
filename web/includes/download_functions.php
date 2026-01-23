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

function downloadEvents(
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

  if (!($exportFormat == 'tar' or $exportFormat == 'zip' or $exportFormat == 'noArchive')) {
    ZM\Error("None or invalid exportFormat specified $exportFormat.");
    return false;
  }

  # Ensure that we are going to be able to do this.
  if (!(@mkdir(ZM_DIR_EXPORTS) or file_exists(ZM_DIR_EXPORTS))) {
    ZM\Fatal('Can\'t create exports dir at \''.ZM_DIR_EXPORTS.'\'');
  }
  chmod(ZM_DIR_EXPORTS, 0700);
  if (!(@mkdir(DIR_EXPORTS_DOWNLOAD) or file_exists(DIR_EXPORTS_DOWNLOAD))) {
    ZM\Fatal('Can\'t create exports dir at \''.DIR_EXPORTS_DOWNLOAD.'\'');
  }
  chmod(DIR_EXPORTS_DOWNLOAD, 0700);

  $export_dir = DIR_EXPORTS_DOWNLOAD.'/'.$export_root;
  define('DOWNLOAD_DIR_EXPORTS', $export_dir);

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

  $archive_path = '';
  if ($exportFormat == 'tar' || $exportFormat == 'zip') {
    $archiveFileName = $export_root.'.'.$exportFormat;
    $archive_path = DIR_EXPORTS_DOWNLOAD.'/'.$archiveFileName;
  }

  foreach (array_keys($events_by_monitor_id) as $mid) {
    $monitor = ZM\Monitor::find_one(['Id'=>$mid]);
    if (!$monitor) {
      ZM\Error("No monitor found for id=$mid");
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
        $maxTimeSecs = $event->EndDateTimeSecs();
        $maxTime = $event->EndDateTime();
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
    @unlink('event_files.txt');

    # We're sending one file at a time to the archive. This will significantly save disk space.
    $command = '';
    if ($exportFormat == 'tar') {
      # We can't just create a tar.gz file and add files to it. We first add everything to the 'tar' file, and then to the 'gz' file.
      $command = 'tar --append --dereference';
      if ($exportStructure == 'flat') {
        $command .= getFlatCommandForTar();
      }
      $command .= ' --file='.escapeshellarg($archive_path);
    } else if ($exportFormat == 'zip') {
      $command .= 'zip ';
      $command .= ($exportStructure == 'flat' ? ' -j ' : '').escapeshellarg($archive_path);
      $command .= $exportCompressed ? ' -9' : ' -0';
    } else if ($exportFormat == 'noArchive') {

    }

    if ($command) {
      $command .= ' \''.$mergedFileName.'\''; # Name of the file to be added
      if (executeShelCommand($command, $deleteFile = $mergedFileName) === false) return false;
    }
  } # end foreach monitor

  generateFileList($exportFormat, $exportStructure, $archive_path, $exportCompressed, $export_dir, $export_root, $exportFileList);

  chdir(DIR_EXPORTS_DOWNLOAD);

  if ($exportFormat == 'tar') {
    # Create an archive if necessary
    //$exportCompressed = true; // For debugging
    if ($exportCompressed) {
      $command = 'gzip '.escapeshellarg($archive_path); # Name of the file to be archived
      if (executeShelCommand($command) === false) return false;
      $archiveFileName .= '.gz';
    }
  } else if ($exportFormat == 'zip') {

  } else if ($exportFormat == 'noArchive') {

  }

  $linkExportFile = [];

  if ($exportFormat == 'noArchive') {
    $returnString = [];
    foreach ($exportFileList as $link) {
      $returnString[] = '?view=download&type='.'mp4'.'&file='.$link.'&export_root='.$export_root;
    }
  } else {
    $returnString = '?view=download&type='.$exportFormat.'&file='.$archiveFileName;
  }
  return $returnString;
} # end function downloadEvents

function generateFileList ($exportFormat, $exportStructure, $archive_path, $exportCompressed, $export_dir, $export_root, $exportFileList) {
  if ($exportFormat != 'tar' && $exportFormat != 'zip') return false;

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

  # Let's add a text file to the archive
  if ($exportFormat == 'tar') {
    $command = 'tar --append --dereference';
    $command .= getFlatCommandForTar(); # We add one file, which means FLAT
    $command .= ' --file='.escapeshellarg($archive_path);
  } else if ($exportFormat == 'zip') {
    $command = 'zip -j '.escapeshellarg($archive_path);
    $command .= $exportCompressed ? ' -9' : ' -0';
  }
  $command .= ' \''.$export_listFile.'\''; # Name of the file to be added
  if (executeShelCommand($command, $deleteFile = $export_listFile) === false) return false;

  # Let's delete the directory, it should already be empty.
  if (!@rmdir($export_dir)) {  
    ZM\Error("Cannot remove '$export_dir' - directory is not empty");
  }
}

function executeShelCommand($command, $deleteFile = '') {
  if (!$command) return false;

  exec($command, $output, $status);
  ZM\Debug("Executing a command: $command");
  $deleteFile = preg_replace('@[/\\\]@', '', $deleteFile); # Let's allow deletion only in the current directory, clear the paths.
  if ($deleteFile) {
    if (!@unlink($deleteFile)) {;
      ZM\Error("Cannot delete file '".DOWNLOAD_DIR_EXPORTS."/$deleteFile'");
    }
  }
  if ($status) {
    ZM\Error("Command '$command' returned with status $status");
    if (isset($output[0])) {
      ZM\Error('First line of output is \''.$output[0].'\'');
    }
    return false;
  }
  return true;
}

function getFlatCommandForTar() {
  $version = @shell_exec('tar --version');
  ZM\Debug("Version tar=$version");

  if (preg_match('/BSD/i', $version)) {
    $command = ' -s \'#^.*/##\'';
  } else {
    $command = ' --xform=\'s#^.+/##x\'';
  }
  return $command;
}
