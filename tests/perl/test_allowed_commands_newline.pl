#!/usr/bin/perl
#
# commandIsAllowed must refuse a command that holds a newline while an
# allow-list is set: the shell runs the text after the newline as a second
# command. Set GENERAL_PM to another General.pm to test it.
#
# Run as: perl tests/perl/test_allowed_commands_newline.pl
#
use strict;
use warnings;
use FindBin;

my $failures = 0;
sub check {
  my ($name, $got, $want) = @_;
  if ($got == $want) { print "ok - $name\n"; }
  else { $failures++; print "FAIL - $name (got $got, want $want)\n"; }
}

my $file = $ENV{GENERAL_PM} || "$FindBin::Bin/../../scripts/ZoneMinder/lib/ZoneMinder/General.pm";
open(my $fh, '<', $file) or die "Can't read $file: $!";
my $src = do { local $/; <$fh> };
close($fh);
my ($chain) = $src =~ /^(our \@COMMAND_CHAINING = [^\n]*\n)/m or die "COMMAND_CHAINING not found\n";
my ($prog) = $src =~ /^(sub commandProgram \{.*?^\}[^\n]*\n)/ms or die "commandProgram not found\n";
my ($allowed) = $src =~ /^(sub commandIsAllowed \{.*?^\}[^\n]*\n)/ms or die "commandIsAllowed not found\n";
eval "$chain $prog $allowed; 1" or die "Can't compile: $@";

my $list = '/bin/true';
check('the listed program is allowed', commandIsAllowed('/bin/true /tmp/event', $list), 1);
check('a semicolon is refused', commandIsAllowed('/bin/true; touch /tmp/x', $list), 0);
check('a newline is refused', commandIsAllowed("/bin/true\ntouch /tmp/x", $list), 0);
check('a carriage return and newline is refused', commandIsAllowed("/bin/true\r\ntouch /tmp/x", $list), 0);
check('process substitution is refused', commandIsAllowed("/bin/true <(touch /tmp/x)", $list), 0);
check('output process substitution is refused', commandIsAllowed("/bin/true >(touch /tmp/x)", $list), 0);
check('a quoted program followed by a newline is refused', commandIsAllowed("\"/bin/true\"\ntouch /tmp/x", $list), 0);
check('no list allows a newline', commandIsAllowed("/bin/true\ntouch /tmp/x", ''), 1);

print $failures ? "$failures failed\n" : "all passed\n";
exit($failures ? 1 : 0);
