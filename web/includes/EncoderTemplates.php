<?php
//
// ZoneMinder Encoder Parameter Templates
// Copyright (C) 2026 ZoneMinder Inc.
//
namespace ZM;

class EncoderTemplates {
  // Static allow-list of recognised AVOption keys per encoder.
  // Used by the advisory lint in monitor-encoder-templates.js to flag
  // keys that ffmpeg will silently ignore. Hand-curated; opaque
  // pass-through options like x264-params/x265-params are listed but
  // their inner sub-options are not (ffmpeg's help text doesn't
  // enumerate them, and runtime introspection is deferred).
  private const VALID_KEYS = [
    'libx264' => [
      'preset', 'tune', 'profile', 'level', 'crf', 'qp', 'b',
      'maxrate', 'bufsize', 'g', 'keyint_min', 'sc_threshold',
      'bf', 'refs', 'pix_fmt', 'x264-params', 'x264opts',
    ],
    'libx265' => [
      'preset', 'tune', 'profile', 'level', 'crf', 'qp', 'b',
      'maxrate', 'bufsize', 'g', 'keyint_min', 'sc_threshold',
      'bf', 'refs', 'pix_fmt', 'x265-params',
    ],
    'h264_nvenc' => [
      'preset', 'tune', 'profile', 'level', 'rc', 'cq', 'qp', 'b',
      'maxrate', 'bufsize', 'g', 'bf', 'spatial-aq', 'temporal-aq',
      'rc-lookahead', 'pix_fmt', 'gpu', 'tuning_info',
    ],
    'hevc_nvenc' => [
      'preset', 'tune', 'profile', 'level', 'rc', 'cq', 'qp', 'b',
      'maxrate', 'bufsize', 'g', 'bf', 'spatial-aq', 'temporal-aq',
      'rc-lookahead', 'pix_fmt', 'gpu', 'tuning_info', 'tier',
    ],
    'h264_vaapi' => [
      'profile', 'level', 'rc_mode', 'qp', 'b', 'maxrate', 'bufsize',
      'g', 'bf', 'pix_fmt', 'low_power', 'idr_interval',
    ],
    'hevc_vaapi' => [
      'profile', 'level', 'rc_mode', 'qp', 'b', 'maxrate', 'bufsize',
      'g', 'bf', 'pix_fmt', 'low_power', 'idr_interval', 'tier',
    ],
  ];

  // Returns the templates dict consumed by monitor.js.php. Shape:
  //   { encoder: { valid_keys: [...], templates: [...] }, ... }
  // valid_keys come from VALID_KEYS; templates come from the DB.
  public static function all(): array {
    $byEncoder = [];
    foreach (self::VALID_KEYS as $enc => $keys) {
      $byEncoder[$enc] = ['valid_keys' => $keys, 'templates' => []];
    }
    $rows = dbFetchAll('SELECT Id, Encoder, Name, Description, Params FROM EncoderTemplates ORDER BY Encoder, Name');
    foreach ($rows as $row) {
      $enc = $row['Encoder'];
      if (!isset($byEncoder[$enc])) {
        // Unknown encoder (e.g. user added a row for an encoder not in
        // VALID_KEYS). No valid_keys -> lint says nothing about it.
        $byEncoder[$enc] = ['valid_keys' => [], 'templates' => []];
      }
      $byEncoder[$enc]['templates'][] = [
        'id'          => (int)$row['Id'],
        'name'        => $row['Name'],
        'description' => $row['Description'] ?? '',
        'params'      => self::paramsTextToObject($row['Params']),
      ];
    }
    return $byEncoder;
  }

  public static function validKeysFor(string $encoder): array {
    return self::VALID_KEYS[$encoder] ?? [];
  }

  // Convert "key=value\nkey=value" text to {key: value} for the
  // JS module's mergeParams. Mirrors the parseParams JS function:
  // splits on \n / , / # (matching av_dict_parse_string), trims keys
  // and values, drops pairs without =, drops empty keys.
  private static function paramsTextToObject(string $text): array {
    $out = [];
    $pairs = preg_split('/[#,\n]/', $text);
    if ($pairs === false) return $out;
    foreach ($pairs as $pair) {
      $idx = strpos($pair, '=');
      if ($idx === false) continue;
      $key = trim(substr($pair, 0, $idx));
      $val = trim(substr($pair, $idx + 1));
      if ($key !== '') $out[$key] = $val;
    }
    return $out;
  }
}
