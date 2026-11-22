#!/usr/bin/env perl
use strict;
use warnings;
use B ();

my $root = defined $ENV{ZM_TEST_ROOT} ? $ENV{ZM_TEST_ROOT} : '.';
my $path = "$root/scripts/zmcontrol.pl.in";

my $text = '';
if (open my $fh, '<', $path) {
  my @lines = <$fh>;
  close $fh;
  my $in = 0;
  for my $line (@lines) {
    if (!$in) {
      if ($line =~ /^\s*sub\s+control_method\b/) {
        $in = 1;
        $text .= $line;
      }
      next;
    }
    $text .= $line;
    (my $trim = $line) =~ s/\r?\n\z//;
    last if $trim eq '}';
  }
} else {
  print "not ok - cannot read $path: $!\n";
  exit 1;
}

if ($text eq '') {
  $text = "sub control_method { my (\$c,\$m)=\@_; return \$c->can(\$m); }\n";
}

eval $text;
if ($@) {
  print "not ok - control_method did not compile: $@";
  exit 1;
}

package ZoneMinder::Base {
  sub find { 1 }
}

package ZoneMinder::Object {
  our @ISA = ('ZoneMinder::Base');
  sub save { 1 }
  sub set { 1 }
}

package ZoneMinder::Control {
  our @ISA = ('ZoneMinder::Object');
  sub new { bless {}, shift }
  sub open { 1 }
  sub get { 1 }
}

package ZoneMinder::Logger {
  sub Fatal { 1 }
}

package Fake::Ctl {
  our @ISA = ('ZoneMinder::Control');
  BEGIN { *Fatal = \&ZoneMinder::Logger::Fatal; }
  sub moveConUp { 1 }
  sub reboot { 1 }
}

package main;

my $c = Fake::Ctl->new();
my $fail = 0;

sub check {
  my ($desc, $got, $want) = @_;
  my $ok = $want ? ($got ? 1 : 0) : ($got ? 0 : 1);
  if ($ok) {
    print "ok - $desc\n";
  } else {
    print "not ok - $desc\n";
    $fail++;
  }
}

check("control_method finds moveConUp", main::control_method($c, 'moveConUp'), 1);
check("control_method finds reboot", main::control_method($c, 'reboot'), 1);
check("control_method rejects base class method open", main::control_method($c, 'open'), 0);
check("control_method rejects base class method get", main::control_method($c, 'get'), 0);
check("control_method rejects imported function Fatal", main::control_method($c, 'Fatal'), 0);
check("control_method rejects Object method save", main::control_method($c, 'save'), 0);
check("control_method rejects Object method set", main::control_method($c, 'set'), 0);
check("control_method rejects Base method find", main::control_method($c, 'find'), 0);
check("control_method rejects unknown method nothing", main::control_method($c, 'nothing'), 0);

exit($fail ? 1 : 0);
