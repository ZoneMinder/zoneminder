use strict;
use warnings;
use Test::More tests => 16;

require_ok('ZoneMinder::Server');

my $parse = ZoneMinder::Server->can('parse_bsd_top_cpu');
ok($parse, 'parse_bsd_top_cpu is available');

# --- single aggregate "CPU states:" line (OpenBSD top's default) --------------

my @r = $parse->("load averages:  0.15,  0.20,  0.18\n"
  ."CPU states:  1.2% user,  0.5% nice,  3.4% sys,  0.1% spin,  0.2% intr, 94.6% idle\n"
  ."Memory: Real: 100M/1G act/tot\n");
is(scalar(@r), 4, 'aggregate line yields four values');
is($r[0], 1.2, 'user read from aggregate line');
is($r[1], 0.5, 'nice read from aggregate line');
is($r[2], 3.4, 'sys read from aggregate line, not swallowed by a misplaced %');
is($r[3], 94.6, 'idle read from aggregate line');

# --- per-core lines are averaged, not halved and not first-wins --------------

@r = $parse->("CPU0 states: 10.0% user,  0.0% nice, 20.0% sys,  0.0% spin,  0.0% intr, 70.0% idle\n"
  ."CPU1 states: 30.0% user,  2.0% nice, 40.0% sys,  0.0% spin,  0.0% intr, 28.0% idle\n");
is($r[0], 20, 'user is the mean of both cores');
is($r[1], 1, 'nice is the mean of both cores');
is($r[2], 30, 'sys is the mean of both cores');
is($r[3], 49, 'idle is the mean of both cores');

# --- padding must not matter -------------------------------------------------

@r = $parse->("CPU states:  0.0% user,  0.0% nice,  0.0% sys,  0.0% spin,  0.0% intr, 100.0% idle");
is($r[3], 100, 'a three-digit idle still matches despite the narrower padding');

@r = $parse->("CPU0 states:\t5.0%\tuser,\t0.0%\tnice,\t5.0%\tsys,\t0.0%\tspin,\t0.0%\tintr,\t90.0%\tidle");
is($r[0], 5, 'tab-separated fields still match');

# --- no match -> empty list, so the caller can warn --------------------------

@r = $parse->('');
is(scalar(@r), 0, 'empty input yields no values');

@r = $parse->("%Cpu(s):  1.0 us,  2.0 sy,  0.0 ni, 97.0 id\n");
is(scalar(@r), 0, 'a Linux top header is not mistaken for BSD output');

@r = $parse->("CPU:  1.2% user,  0.0% nice,  3.4% system,  0.2% interrupt, 95.2% idle\n");
is(scalar(@r), 0, 'FreeBSD output is left to the FreeBSD branch');
