#!/usr/bin/perl
#
# Event::Event() built the Events INSERT with the event Cause and Notes dropped into single-quoted
# literals unescaped. Both come from outside the daemon: Cause carries the alarmed zone names (any
# Monitors=Edit user sets them) and the external trigger's cause/text (zmtrigger.pl, unauthenticated
# on TCP 6802), so a zone name with a quote could close the literal and read any value the DB user
# can see into the recorded event. The zmc monitor-loading queries and the Servers-by-name lookup
# interpolated their string match values the same way. refs GHSA-hc5x-7jfw-r692
#
# release-1.36 does not fully build on the dev box (FFmpeg API drift), so this reads the real
# sources and checks each of those SQL values now goes through zmDbEscapeString and none is still
# built from the raw string. Run: perl tests/perl/test_event_sql_escaping.pl
#
use strict;
use warnings;
use FindBin;

my $failures = 0;
my $passes = 0;
sub check {
  my ($name, $cond) = @_;
  if ($cond) { $passes++; print "ok - $name\n"; }
  else { $failures++; print "FAIL - $name\n"; }
}

my $src_dir = "$FindBin::Bin/../../src";

# Read a source file with // comment lines dropped, so an explanatory comment that names old raw
# code cannot satisfy or defeat a check.
sub read_code {
  my ($file) = @_;
  open(my $fh, '<', "$src_dir/$file") or die "cannot open $src_dir/$file: $!";
  my $code = do { local $/; <$fh> };
  close($fh);
  $code =~ s{^\s*//.*$}{}mg;   # whole-line // comments
  return $code;
}

my $event   = read_code('zm_event.cpp');
my $config  = read_code('zm_config.cpp');
my $monitor = read_code('zm_monitor.cpp');

# --- zm_event.cpp: the Events INSERT escapes Cause and Notes -----------------
check('event INSERT escapes Cause',
  $event =~ /zmDbEscapeString\(cause\)\.c_str\(\)/);
check('event INSERT escapes Notes',
  $event =~ /zmDbEscapeString\(notes\)\.c_str\(\)/);
# The old raw pair (cause.c_str() then notes.c_str() as adjacent INSERT args) must be gone.
check('event INSERT no longer passes raw Cause/Notes',
  $event !~ /\bcause\.c_str\(\)\s*,\s*\n\s*notes\.c_str\(\)\s*,/);

# --- zm_config.cpp: Servers lookup by name is escaped ------------------------
check('Servers-by-name lookup escapes SERVER_NAME',
  $config =~ /zmDbEscapeString\(staticConfig\.SERVER_NAME\)\.c_str\(\)/);
check('Servers-by-name lookup no longer passes raw SERVER_NAME',
  $config !~ /WHERE `Name`='%s'",\s*\n\s*staticConfig\.SERVER_NAME\.c_str\(\)/);

# --- zm_monitor.cpp: monitor-loading match values are escaped ----------------
check('LoadLocalMonitors escapes Device',
  $monitor =~ /`Device`='" \+ zmDbEscapeString\(device\) \+ "'/);
check('LoadRemoteMonitors escapes Protocol/Host/Port/Path',
  $monitor =~ /zmDbEscapeString\(protocol\)\.c_str\(\)/ &&
  $monitor =~ /zmDbEscapeString\(host\)\.c_str\(\)/ &&
  $monitor =~ /zmDbEscapeString\(port\)\.c_str\(\)/ &&
  $monitor =~ /zmDbEscapeString\(path\)\.c_str\(\)/);
my $file_escapes = () = $monitor =~ /zmDbEscapeString\(file\)/g;
check('both file-path monitor queries escape the path', $file_escapes >= 2);

# None of the monitor-loading match values is still concatenated raw.
check('LoadLocalMonitors no longer concatenates raw device',
  $monitor !~ /`Device`='" \+ std::string\(device\)/);
check('file-path monitor queries no longer concatenate raw file',
  $monitor !~ /`Path`\s*=?\s*'" \+ std::string\(file\)/);
check('LoadRemoteMonitors no longer passes raw protocol/host/port/path',
  $monitor !~ /AND `Path` = '%s'",\s*protocol,\s*host,\s*port,\s*path\)/);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
