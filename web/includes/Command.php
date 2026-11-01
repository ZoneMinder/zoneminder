<?php
//
// ZoneMinder command allow-list
// Copyright (C) 2026 ZoneMinder LLC
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
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
//
// Two places in ZoneMinder run a command the operator typed: a filter with
// "Execute command", and a scheduled task. Both run it as the web user through
// a shell, which is close to shell access on the server.
//
// ZM_ALLOWED_COMMANDS narrows that to a list the administrator chose. Empty,
// which is the default and what every existing install has, places no
// restriction and keeps the behaviour ZoneMinder has always had.
//
// The same rule is implemented in Perl, in ZoneMinder::General, because
// zmfilter.pl has to apply it at the moment it runs a command and not merely
// when the web ui saved one. tests/php/test_allowed_commands.php checks the
// two against each other.
//

// The characters that let one command start another. While a list is in
// force these are refused outright: a program being on the list says nothing
// about what else runs alongside it if the command can chain.
//
// Redirection is not here. It does not start a program, and refusing it would
// stop the ordinary "... >> /var/log/mine.log" that people reasonably write.
define('ZM_COMMAND_CHAINING', array(';', '|', '&', '`', '$('));

// The program a command invokes: its first word, with any surrounding quotes
// removed. Null when there is not one.
//
// Only the program is matched, so arguments may vary from run to run -- which
// they must, since zmfilter.pl appends the event path to whatever the filter
// has stored.
function commandProgram($command) {
  if (!is_string($command)) return null;
  $command = trim($command);
  if ($command === '') return null;

  // A quoted path may contain spaces, so the quoted run has to be taken
  // before splitting on whitespace -- splitting first would cut
  // "/usr/local/bin/my script.sh" in half and compare the wrong thing.
  if (($command[0] === '"') or ($command[0] === "'")) {
    $quote = $command[0];
    $end = strpos($command, $quote, 1);
    // An unterminated quote is not a program name we can read.
    if ($end === false) return null;
    $program = substr($command, 1, $end - 1);
    return ($program === '') ? null : $program;
  }

  $parts = preg_split('/\s+/', $command, 2);
  $program = $parts[0];
  return ($program === '') ? null : $program;
}

// Parse the configured list into the programs it names.
function commandAllowList($setting) {
  $allowed = array();
  if (!is_string($setting)) return $allowed;
  foreach (explode(',', $setting) as $entry) {
    $entry = trim($entry);
    if ($entry !== '') $allowed[] = $entry;
  }
  return $allowed;
}

// May $command be run, given the configured list?
//
// $allowed is the raw setting; an empty one means no restriction. Pure, so
// the rule can be tested without a database.
function commandIsAllowed($command, $allowed) {
  $list = commandAllowList($allowed);
  // No list configured: unrestricted, as ZoneMinder has always been.
  if (!count($list)) return true;

  if (!is_string($command)) return false;
  foreach (ZM_COMMAND_CHAINING as $chain) {
    if (strpos($command, $chain) !== false) return false;
  }

  $program = commandProgram($command);
  if ($program === null) return false;
  return in_array($program, $list, true);
}

// The same question, asked of the running configuration.
function commandAllowedByConfig($command) {
  return commandIsAllowed($command, defined('ZM_ALLOWED_COMMANDS') ? ZM_ALLOWED_COMMANDS : '');
}

// What to tell someone whose command was refused, naming what is allowed so
// the answer is actionable rather than just "no".
function commandRefusedMessage() {
  $list = commandAllowList(defined('ZM_ALLOWED_COMMANDS') ? ZM_ALLOWED_COMMANDS : '');
  return translate('CommandNotAllowed').' '.implode(', ', $list);
}
