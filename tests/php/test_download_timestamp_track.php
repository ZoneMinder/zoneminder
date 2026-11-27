<?php
// ZM_OPT_EXPORT_TIMESTAMP_TRACK in web/includes/download_functions.php.
//
//  1. parseVideoProbe() reads the stream signature and container duration
//  2. vttCuesForEvent() writes one cue per second, a short last cue, the
//     offset into the merged file, and a label carrying the UTC offset
//  3. downloadEvents() gives each group of matching events (one ffmpeg run)
//     its own track, with cue offsets restarting at 0 per group and advancing
//     by the probed file duration rather than the event's DB Length
//
// With the option off the merge is a plain stream copy (checked through
// exportTimestampTrackOptions('') here; test_download_events.php runs
// downloadEvents() with the option undefined).
//
// Fake event storage in a temp dir, stub ffmpeg/zip first on PATH.
//
// Run: php tests/php/test_download_timestamp_track.php

namespace ZM {
  function Error($m) {}
  function Warning($m) {}
  function Debug($m) {}
  function Info($m) {}
  function Fatal($m) { throw new \Exception("Fatal: $m"); }

  class Event {
    public static $fixtures = [];
    public $f;
    public $StartDateTime;
    public function __construct($id) {
      $this->f = self::$fixtures[$id] ?? ['Id'=>null];
      $this->StartDateTime = $this->f['Start'] ?? '';
    }
    public function Id() { return $this->f['Id']; }
    public function canView() { return !empty($this->f['Id']); }
    public function MonitorId() { return 1; }
    public function DefaultVideo() { return $this->f['Video'] ?? ''; }
    public function Path() { return $this->f['Path'] ?? ''; }
    public function Length() { return $this->f['Length'] ?? 0; }
    public function StartDateTime() { return $this->StartDateTime; }
    public function StartDateTimeSecs() { return strtotime($this->StartDateTime); }
    public function EndDateTime() { return $this->f['End'] ?? ''; }
    public function EndDateTimeSecs() { return strtotime($this->f['End'] ?? '') ?: 0; }
    public function GenerateVideo() { return null; }
  }
  class Monitor {
    public static function find_one($p) { return new Monitor(); }
    public function Name() { return 'Front'; }
  }
}

namespace {
  date_default_timezone_set('UTC');
  $user = null;
  function canView($a) { return true; }
  function to_string($t) { return empty($t) ? '' : (is_array($t) ? implode(', ', $t) : strval($t)); }
  function deletePath($p) {
    if (is_dir($p) and !is_link($p)) {
      foreach (scandir($p) as $e) if ($e != '.' and $e != '..') deletePath("$p/$e");
      rmdir($p);
    } else {
      @unlink($p);
    }
  }

  $tests = 0;
  $failures = 0;
  function check($label, $got, $want) {
    global $tests, $failures;
    $tests++;
    if ($got === $want) {
      echo "  ok  - $label\n";
    } else {
      $failures++;
      echo "  NOT OK - $label\n    got:  ".var_export($got, true)."\n    want: ".var_export($want, true)."\n";
    }
  }

  $root = sys_get_temp_dir().'/zm_dl_vtt_'.getmypid();
  foreach (['bin', 'exports', 'ev1', 'ev2', 'ev3'] as $d) mkdir("$root/$d", 0700, true);

  // ffmpeg: a -hide_banner probe prints the stream and duration stored in the
  // fake video file ("codec WxH duration"); a merge logs its arguments and the
  // VTT file it was given, one numbered set per run.
  file_put_contents("$root/bin/ffmpeg", <<<'SH'
#!/bin/sh
for last; do :; done
if [ "$1" = "-hide_banner" ]; then
  read codec size dur < "$3"
  echo "  Duration: $dur, start: 0.000000, bitrate: 100 kb/s"
  echo "  Stream #0:0: Video: $codec (Main), yuv420p, $size, 5 fps"
  exit 0
fi
n=$(ls "$ZM_TEST_ROOT"/run*.args 2>/dev/null | wc -l)
echo "$*" > "$ZM_TEST_ROOT/run$n.args"
[ -f timestamps.vtt ] && cp timestamps.vtt "$ZM_TEST_ROOT/run$n.vtt"
echo merged > "$last"
SH
  );
  file_put_contents("$root/bin/zip", "#!/bin/sh\nexit 0\n");
  foreach (['ffmpeg', 'zip'] as $p) chmod("$root/bin/$p", 0755);
  putenv("PATH=$root/bin:/usr/bin:/bin");
  putenv("ZM_TEST_ROOT=$root");

  define('ZM_PATH_FFMPEG', "$root/bin/ffmpeg");
  define('ZM_DIR_EXPORTS', "$root/exports");
  define('DIR_EXPORTS_DOWNLOAD', "$root/exports/download");

  $src = file_get_contents(__DIR__.'/../../web/includes/functions.php');
  $start = strpos($src, 'function findVideoEventFile');
  $end = strpos($src, "\n}\n", $start) + 3;
  eval(substr($src, $start, $end - $start));
  require_once __DIR__.'/../../web/includes/download_functions.php';

  // 1. probe parsing
  $p = parseVideoProbe("  Duration: 01:02:03.50, start: 0.0\n  Stream #0:0: Video: h264 (High), yuv420p, 320x240, 5 fps");
  check('probe signature', $p['signature'], 'h264 320x240');
  check('probe duration', $p['duration'], 3723.5);
  check('N/A duration is 0', parseVideoProbe('  Duration: N/A, bitrate: N/A')['duration'], 0.0);

  // 2. cue generation
  $t0 = strtotime('2026-01-01 10:00:00');
  check('whole seconds give one cue each',
    vttCuesForEvent($t0, 2, 0),
    "00:00:00.000 --> 00:00:01.000\n2026-01-01 10:00:00+00:00\n\n".
    "00:00:01.000 --> 00:00:02.000\n2026-01-01 10:00:01+00:00\n\n");
  check('fractional duration gives a short last cue, offset applied',
    vttCuesForEvent($t0, 1.5, 3600),
    "01:00:00.000 --> 01:00:01.000\n2026-01-01 10:00:00+00:00\n\n".
    "01:00:01.000 --> 01:00:01.500\n2026-01-01 10:00:01+00:00\n\n");
  check('zero duration gives no cues', vttCuesForEvent($t0, 0, 5), '');
  check('timestamp carries into minutes', formatVttTimestamp(59.9996), '00:01:00.000');
  check('plain copy without a track', exportTimestampTrackOptions('', $t0), ' -c copy');
  check('track options map the subtitle and set creation_time in UTC',
    exportTimestampTrackOptions('timestamps.vtt', $t0),
    " -i 'timestamps.vtt' -map 0:v -map 0:a? -map 1 -c:v copy -c:a copy -c:s mov_text".
    " -metadata:s:s:0 language=eng -metadata:s:s:0 title=timestamp -metadata creation_time='2026-01-01T10:00:00Z'");

  // 3. per-group tracks. ev1 and ev2 share a stream and merge together; ev1's
  // file runs 2.5s though the DB says 3s, so ev2's cues start at 2.5s. ev3 has
  // a different stream and goes in a second run whose cues start at 0.
  file_put_contents("$root/ev1/1-video.mp4", "h264 320x240 00:00:02.50\n");
  file_put_contents("$root/ev2/2-video.mp4", "h264 320x240 00:00:01.00\n");
  file_put_contents("$root/ev3/3-video.mp4", "hevc 640x480 00:00:02.00\n");
  ZM\Event::$fixtures = [
    1 => ['Id'=>1, 'Path'=>"$root/ev1", 'Video'=>'1-video.mp4', 'Length'=>3, 'Start'=>'2026-01-01 10:00:00', 'End'=>'2026-01-01 10:00:03'],
    2 => ['Id'=>2, 'Path'=>"$root/ev2", 'Video'=>'2-video.mp4', 'Length'=>1, 'Start'=>'2026-01-01 10:05:00', 'End'=>'2026-01-01 10:05:01'],
    3 => ['Id'=>3, 'Path'=>"$root/ev3", 'Video'=>'3-video.mp4', 'Length'=>2, 'Start'=>'2026-01-01 10:10:00', 'End'=>'2026-01-01 10:10:02'],
  ];

  define('ZM_OPT_EXPORT_TIMESTAMP_TRACK', true);
  $r = downloadEvents([1, 2, 3], 'Export', 'zip', false);
  check('export succeeds', $r, '?view=download&type=zip&file=Export.zip');
  check('first group track: ev1 then ev2 at the probed 2.5s',
    @file_get_contents("$root/run0.vtt"),
    "WEBVTT\n\n".
    "00:00:00.000 --> 00:00:01.000\n2026-01-01 10:00:00+00:00\n\n".
    "00:00:01.000 --> 00:00:02.000\n2026-01-01 10:00:01+00:00\n\n".
    "00:00:02.000 --> 00:00:02.500\n2026-01-01 10:00:02+00:00\n\n".
    "00:00:02.500 --> 00:00:03.500\n2026-01-01 10:05:00+00:00\n\n");
  check('second group track restarts at 0',
    @file_get_contents("$root/run1.vtt"),
    "WEBVTT\n\n".
    "00:00:00.000 --> 00:00:01.000\n2026-01-01 10:10:00+00:00\n\n".
    "00:00:01.000 --> 00:00:02.000\n2026-01-01 10:10:01+00:00\n\n");
  $args1 = (string)@file_get_contents("$root/run1.args");
  check('second run maps the subtitle track', strpos($args1, '-map 1 -c:v copy -c:a copy -c:s mov_text') !== false, true);
  check('second run creation_time is its own group start', strpos($args1, 'creation_time=2026-01-01T10:10:00Z') !== false, true);
  check('VTT file is removed after the runs', file_exists(DIR_EXPORTS_DOWNLOAD.'/Export/timestamps.vtt'), false);

  deletePath($root);
  echo "\n$tests tests, $failures failures\n";
  exit($failures ? 1 : 0);
}
