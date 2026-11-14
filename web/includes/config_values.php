<?php
// Whether every "ZM_A=1;ZM_B=hashed" condition in Requires holds for the values by option name.
function configRequirementsMet($requires, $values) {
  foreach (explode(';', $requires) as $req) {
    if (!preg_match('/^(ZM_\w+)=(.+)$/', trim($req), $match)) return false;
    if (!isset($values[$match[1]]) or $values[$match[1]] != $match[2]) return false;
  }
  return true;
}

// Whether $value can be stored for an option of $type; empty only if its default is empty.
function configValueIsValid($value, $type, $default='') {
  if ($value === '' and $default === '') return true;
  switch ($type) {
  case 'integer':
    return preg_match('/^-?\d+$/', $value) === 1;
  case 'decimal':
    return preg_match('/^-?\d+(\.\d+)?$/', $value) === 1;
  case 'hexadecimal':
    return preg_match('/^(0x)?[0-9a-f]+$/i', $value) === 1;
  }
  return true;
}
