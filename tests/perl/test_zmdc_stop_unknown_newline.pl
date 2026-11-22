#!/usr/bin/perl
# stop() on a command zmdc does not know reports it with dPrint, whose text is
# echoed to the client verbatim, so the message must end in a newline.
use strict;
use warnings;
use FindBin;

my $path = "$FindBin::Bin/../../scripts/zmdc.pl.in";
open(my $fh, '<', $path) or die "Cannot read $path: $!";
my $src = do { local $/; <$fh> };
close($fh);
$src =~ /^(sub stop \{.*?^\})/ms or die "stop not found in $path";
my $code = $1;

my @printed;
sub ZoneMinder::Logger::WARNING () { 2 }
{
  package ZMServer;
  our (%cmd_hash);
  sub dPrint { push @printed, [@_] }
  sub send_stop { die "send_stop must not be reached\n" }
  eval $code;
  die $@ if $@;
}

my $failures = 0;
sub check { my ($n, $ok) = @_; print(($ok ? 'ok' : 'FAIL')." - $n\n"); $failures++ if !$ok; }

ZMServer::stop('zmc', '-m', '1');
check('unknown command reported once', @printed == 1);
check('message names the command', $printed[0][1] =~ /Can't find process with command of 'zmc -m 1'/);
check('message ends with a newline', $printed[0][1] =~ /\n\z/);
exit($failures ? 1 : 0);
