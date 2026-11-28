#!/usr/bin/perl
# The X10 server socket takes 'command;unit' messages from the web UI.
#   'on'/'off' with no unit must be refused without killing the daemon, and
#   unit codes must be normalised: '05' and '5' name the same Appliance.
use strict;
use warnings;
use FindBin;
use Cwd;
require "$FindBin::Bin/zmx10_harness.pl";
our $Dir;

# Run against another copy of the template (e.g. the original from git)
# without changing the harness or the worktree: ZMX10_SRC replaces just the
# template read, the same way test_zmx10_timed_stop.pl does.
my $harness_read_file = \&read_file;
{
  no warnings 'redefine';
  *read_file = sub {
    my $path = shift;
    return $harness_read_file->($ENV{ZMX10_SRC})
      if $ENV{ZMX10_SRC} && $path =~ m{/scripts/zmx10\.pl\.in$};
    return $harness_read_file->($path);
  };
}

# The harness client sends the message without a trailing newline, exactly as
# zmx10.pl's own client does (it prints the message and shuts the write side
# down), so <CLIENT> in the server returns the message at EOF.
sub call_lines {
  my $pat = shift;
  return grep { /^\Q$pat\E\b/ } split(/\n/, read_file("$Dir/calls.log"));
}

sub restart_with_clean_log {
  zmx10_stop();
  system('rm', '-rf', $Dir) if $Dir;
  zmx10_start(rows => [ {Id=>1, Name=>'m1', Activation=>'', AlarmInput=>'', AlarmOutput=>''} ]);
  sleep(1);
}

# (1) 'on' without a unit must not be served by dereferencing an undefined
# device: the daemon has to stay up and handle the next valid command.
restart_with_clean_log();
zmx10_send('on');
sleep(0.5);
check("'on' with no unit does not kill the daemon", zmx10_alive());
zmx10_send('on;5');
sleep(0.5);
check("daemon still acts on 'on;5' after the bad message", zmx10_calls('APPLIANCE_ON') == 1);

# (2) 'on;05' and 'on;5' are the same unit code: only one Appliance may be
# constructed and both messages must switch that one unit on.
restart_with_clean_log();
zmx10_send('on;05');
zmx10_send('on;5');
sleep(0.5);
check("'on;05' and 'on;5' construct exactly one Appliance", zmx10_calls('APPLIANCE_NEW') == 1);
my @ons = call_lines('APPLIANCE_ON');
check('both commands switch the same unit 5 on', @ons == 2 && !(grep { $_ ne 'APPLIANCE_ON 5' } @ons));

# (3) Unit codes outside 1..16 are still refused, and construct nothing.
restart_with_clean_log();
my $r17 = zmx10_send('on;17');
my $r0  = zmx10_send('on;0');
sleep(0.5);
check("'on;17' is rejected", ($r17 // '') =~ /Invalid unit code '17'/);
check("'on;0' is rejected", ($r0 // '') =~ /Invalid unit code '0'/);
check('rejected codes construct no Appliance', zmx10_calls('APPLIANCE_NEW') == 0);
check('rejected codes switch nothing on', zmx10_calls('APPLIANCE_ON') == 0);
check('daemon survives the rejected codes', zmx10_alive());

finish();
