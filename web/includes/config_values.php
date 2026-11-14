<?php
// Whether every "ZM_A=1;ZM_B=hashed" condition in Requires holds for the values by option name.
function configRequirementsMet($requires, $values) {
  foreach (explode(';', $requires) as $req) {
    if (!preg_match('/^(ZM_\w+)=(.+)$/', trim($req), $match)) return false;
    if (!isset($values[$match[1]]) or $values[$match[1]] != $match[2]) return false;
  }
  return true;
}
