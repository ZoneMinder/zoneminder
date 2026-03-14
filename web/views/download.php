<?php
//
// ZoneMinder file download processing
// Copyright (C) 2026
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

class downloadingGeneratedEventFile {
    private $filenamePath;
    private $exportDir;
    private $exportRoot;
    private $filename;
    public $handle = NULL;
    public $fileType;
    private $mimetype;
    private $fileExt;
    private $connkey;

    public function __construct() {
      register_shutdown_function(array($this, 'callRegisteredShutdown'));
      $this->fileType = isset($_REQUEST['type']) ? $_REQUEST['type'] : '';

      switch ($this->fileType) {
        case 'tar.gz':
          $this->mimetype = 'gzip';
          $this->fileExt = 'tar.gz';
          break;
        case 'tar':
          $this->mimetype = 'tar';
          $this->fileExt = 'tar';
          break;
        case 'zip':
          $this->mimetype = 'zip';
          $this->fileExt = 'zip';
          break;
        case 'mp4':
          $this->mimetype = 'mp4';
          $this->fileExt = 'mp4';
          break;
        default:
          $this->mimetype = NULL;
          $this->fileExt = NULL;
      }

      $this->connkey = isset($_REQUEST['connkey'])?$_REQUEST['connkey']:'';
      $this->filename = isset($_REQUEST['file'])?$_REQUEST['file']:"zmExport_$this->connkey.$this->fileExt";
      $this->filename = str_replace('/', '', $this->filename); # protect system files. must be a filename, not a path
      $this->exportRoot = isset($_REQUEST['export_root'])?$_REQUEST['export_root']."/":"";
      $this->exportRoot = str_replace('/', '', $this->exportRoot); # protect system files. must be a export_root, not a path

      if ($this->exportRoot) {
        $this->filenamePath = DIR_EXPORTS_DOWNLOAD.'/'. $this->exportRoot . '/' . $this->filename;
        $this->exportDir = DIR_EXPORTS_DOWNLOAD.'/'.$this->exportRoot;
      } else {
        $this->filenamePath = DIR_EXPORTS_DOWNLOAD.'/'.$this->filename;
        $this->exportDir = DIR_EXPORTS_DOWNLOAD;
      }
    }

    public function callRegisteredShutdown() {
      $this->removeTmpFiles();
    }

    public function removeTmpFiles () {
      if ($this->handle) fclose($this->handle);

      unlink($this->exportDir.'/'.$this->filename.'.lock'); # Delete download flag file
      unlink($this->filenamePath); # Delete the downloaded file
      # We try to delete the directory (if it is not the main export directory) where the downloaded file was stored.
      if ($this->exportDir != ZM_DIR_EXPORTS)
        if (@rmdir($this->exportDir)) @rmdir(DIR_EXPORTS_DOWNLOAD);
    }

    public function download() {
      if (is_readable($this->filenamePath)) {
        # Let's set a flag that the file has started downloading and mark the download start time.
        # In the future, we will delete generated files that have not started downloading within XX minutes.
        file_put_contents($this->exportDir.'/'.$this->filename.'.lock', strtotime(date("Y-m-d H:i:s")), LOCK_EX);

        while (ob_get_level()) {
          ob_end_clean();
        }

        header("Content-Type: application/$this->mimetype");
        header("Content-Length: ". filesize($this->filenamePath).";");
        header('Content-Disposition: attachment; filename=' . basename($this->filename));
        header('Content-Transfer-Encoding: binary');
        header("Connection: close"); # Close connection after downloading
        @ini_set( 'max_execution_time', 0 );
        @set_time_limit(0);

        if (0) { # ToDo It needs to be moved to the settings
          # DOWNLOAD IN ONE SEGMENT
          if ( !@readfile($this->filenamePath) ) {
            ZM\Error("Error sending $this->filenamePath");
          }
        } else {
          # DOWNLOAD IN PARTS
          $this->handle = fopen($this->filenamePath, "r");
          $chunk_size = 1000000;
          $bytes_sent = 0;
          ignore_user_abort(false); # Disable ignoring user interruptions to prevent the script from running indefinitely if the user interrupts the download.
          $canceled = false;
          while ($chunk = fread($this->handle, $chunk_size)) {
            print $chunk;
            $bytes_sent += strlen($chunk);
          }
        }
      } else {
        header('HTTP/1.0 204 No Content'); # So that there is no blank page! And we need to visually indicate that the file is missing!
        ZM\Error($filenamePath.' does not exist or is not readable.');
      }
    }
}


if (!(canView('Events') or canView('Snapshots'))) {
  $view = 'error';
  return;
}

$downloading = new downloadingGeneratedEventFile();

if ( !$downloading->fileType ) {
  ZM\Error("No file type given to download.php. Please specify a 'mp4', 'tar' or 'zip' file.");
  return;
}

$downloading->download();

?>
