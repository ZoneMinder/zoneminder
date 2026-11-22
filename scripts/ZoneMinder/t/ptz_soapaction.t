#!/usr/bin/perl
#
# Every ONVIF SOAP request must name the operation it is performing in the
# SOAP action URI: <Stop xmlns="http://www.onvif.org/ver20/ptz/wsdl"> has to be
# sent with action ".../ptz/wsdl/Stop".  Cameras that check the action reject
# the request otherwise, which is how "the camera never stops" happens.
#
# The Control modules cannot be loaded here (their dependencies, e.g. DateTime,
# are not installed in the test environment), so this test reads their source
# as text instead: each file is split into subs, and inside a sub each action
# URI (.../wsdl/<Op>, excluding the body's own xmlns declaration) is paired
# with the operation of the closest preceding <s:Body>/<Body> element.

use strict;
use warnings;
use File::Basename qw(basename dirname);
use File::Spec;
use Test::More;

my $here = dirname(File::Spec->rel2abs(__FILE__));
my $lib = $ENV{ZM_CONTROL_LIB}
  || File::Spec->catdir($here, File::Spec->updir, 'lib', 'ZoneMinder', 'Control');

BAIL_OUT("no Control library at $lib") unless -d $lib;

my @checks;    # [ module, sub, op, action ]

for my $file (sort glob(File::Spec->catfile($lib, '*.pm'))) {
  open my $fh, '<', $file or BAIL_OUT("cannot read $file: $!");
  local $/;
  my $src = <$fh>;
  close $fh;

  (my $module = basename($file)) =~ s/\.pm$//;

  # Locate every sub and the extent of its body.
  my @starts;
  while ($src =~ /^sub\s+(\w+)/mg) {
    push @starts, [ $1, $-[0] ];
  }

  for my $i (0 .. $#starts) {
    my ($name, $start) = @{ $starts[$i] };
    my $end = ($i < $#starts) ? $starts[$i + 1][1] : length($src);
    my $body = substr($src, $start, $end - $start);

    # Operation elements, with their offset in the sub.
    my @ops;
    while ($body =~ /<(?:\w+:)?Body\b[^>]*>\s*<([\w:.-]+)/g) {
      (my $op = $1) =~ s/^.*://;
      push @ops, [ $-[0], $op ];
    }
    next unless @ops;

    # Action URIs, skipping the namespace declarations inside the bodies.
    my @actions;
    while ($body =~ /(http:\/\/www\.onvif\.org\/ver\d+\/\w+\/wsdl\/(\w+))/g) {
      my ($pos, $action) = ($-[1], $2);
      my $before = substr($body, 0, $pos);
      next if $before =~ /xmlns="$/;
      push @actions, [ $pos, $action ];
    }
    next unless @actions;

    # A sub may send more than one request (autoStop sends a ContinuousMove
    # and then a Stop).  When the counts line up, pair them in source order --
    # that also covers modules that declare the action before the body.  If
    # they do not line up, fall back to the nearest operation for each action.
    my @pairs;
    if (@ops == @actions) {
      @pairs = map { [ $ops[$_][1], $actions[$_][1] ] } 0 .. $#ops;
    } else {
      my %used;
      for my $a (@actions) {
        my ($pos, $action) = @$a;
        my ($best) = sort { abs($a->[0] - $pos) <=> abs($b->[0] - $pos) }
          grep { !$used{$_->[0]} } @ops;
        next unless $best;
        $used{ $best->[0] } = 1;
        push @pairs, [ $best->[1], $action ];
      }
    }

    push @checks, map { [ $module, $name, $_->[0], $_->[1] ] } @pairs;
  }
}

BAIL_OUT("no SOAP requests found under $lib") unless @checks;

diag("checking $lib");
diag(sprintf('%-24s %-20s %-22s %s', 'module', 'sub', 'body operation', 'action'));
for my $c (@checks) {
  diag(sprintf('%-24s %-20s %-22s %s', @$c));
}

plan tests => scalar(@checks);

for my $c (@checks) {
  my ($module, $name, $op, $action) = @$c;
  ok($action eq $op, "$module\::$name sends <$op> with action <$op>")
    or diag("$module\::$name body is <$op> but action is <$action>");
}
