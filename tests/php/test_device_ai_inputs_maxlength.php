<?php

/**
 * Verify maxlength attributes on device/AI modal inputs match schema column sizes.
 * Usage: php test_device_ai_inputs_maxlength.php [repo_root]
 */

$root = $argv[1] ?? __DIR__ . '/../..';

$cases = array(
  array('web/ajax/modals/device.php', 'newDevice[KeyString]', 32, 'db/zm_create.sql.in', null, 'KeyString'),
  array('web/ajax/modals/ai_dataset.php', 'newDataset[Name]', 64, 'db/AI_Models.sql', 'AI_Datasets', 'Name'),
  array('web/ajax/modals/ai_dataset.php', 'newDataset[Version]', 32, 'db/AI_Models.sql', 'AI_Datasets', 'Version'),
  array('web/ajax/modals/ai_model.php', 'newModel[Name]', 64, 'db/AI_Models.sql', 'AI_Models', 'Name'),
  array('web/ajax/modals/ai_model.php', 'newModel[Version]', 32, 'db/AI_Models.sql', 'AI_Models', 'Version'),
  array('web/ajax/modals/ai_model.php', 'newModel[ModelPath]', 255, 'db/AI_Models.sql', 'AI_Models', 'ModelPath'),
  array('web/ajax/modals/ai_class.php', 'newClass[ClassName]', 64, 'db/AI_Models.sql', 'AI_Object_Classes', 'ClassName'),
);

$failures = 0;

foreach ($cases as $case) {
  list($file, $name, $expected, $schemaFile, $table, $column) = $case;
  $label = $file . ' ' . $name;
  $path = $root . '/' . $file;
  $schemaPath = $root . '/' . $schemaFile;

  if (!is_file($path)) {
    echo "FAIL $label: missing file $path\n";
    $failures++;
    continue;
  }

  $lines = file($path, FILE_IGNORE_NEW_LINES);
  $needle = 'name="' . $name . '"';
  $htmlMax = null;
  $foundLine = false;
  foreach ($lines as $line) {
    if (strpos($line, $needle) === false) {
      continue;
    }
    $foundLine = true;
    $start = strpos($line, '<input');
    if ($start === false) {
      continue;
    }
    $rest = substr($line, $start);
    $selfClose = strpos($rest, '/>');
    if ($selfClose !== false) {
      $tag = substr($rest, 0, $selfClose + 2);
    } else {
      $close = strpos($rest, '>');
      $tag = $close === false ? $rest : substr($rest, 0, $close + 1);
    }
    if (preg_match('/maxlength="(\d+)"/', $tag, $m)) {
      $htmlMax = (int)$m[1];
    }
    break;
  }

  if (!$foundLine) {
    echo "FAIL $label: no line containing $needle\n";
    $failures++;
    continue;
  }

  $schemaSize = null;
  if (!is_file($schemaPath)) {
    echo "FAIL $label: missing schema $schemaPath\n";
    $failures++;
    continue;
  }
  $schemaText = file_get_contents($schemaPath);
  if ($table !== null) {
    $start = strpos($schemaText, 'CREATE TABLE `' . $table . '`');
    if ($start !== false) {
      $end = strpos($schemaText, ');', $start);
      if ($end === false) {
        $end = strpos($schemaText, ';', $start);
      }
      $schemaText = $end === false ? substr($schemaText, $start) : substr($schemaText, $start, $end - $start + 1);
    }
  }
  if (preg_match('/`' . $column . '`\s+varchar\((\d+)\)/', $schemaText, $m)) {
    $schemaSize = (int)$m[1];
  }

  if ($htmlMax === null) {
    echo "FAIL $label: no maxlength attribute (expected $expected)\n";
    $failures++;
    continue;
  }
  if ($htmlMax !== $expected) {
    echo "FAIL $label: maxlength=$htmlMax expected $expected\n";
    $failures++;
    continue;
  }
  if ($schemaSize === null) {
    echo "FAIL $label: schema column $column not found in $schemaFile\n";
    $failures++;
    continue;
  }
  if ($schemaSize !== $expected) {
    echo "FAIL $label: schema varchar($schemaSize) expected $expected\n";
    $failures++;
    continue;
  }

  echo "ok $label maxlength=$htmlMax schema=varchar($schemaSize)\n";
}

$total = count($cases);
if ($failures > 0) {
  echo "SUMMARY: " . ($total - $failures) . "/$total passed, $failures failed\n";
  exit(1);
}

echo "SUMMARY: $total/$total passed\n";
exit(0);
