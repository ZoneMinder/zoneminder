#!/usr/bin/perl
# With AutoDelete, checkFilter must send the summary email before it deletes
# the events, or the links and attachments in it point at nothing.
# Structural test on the template.
use strict;
use warnings;
use FindBin;

my $file = "$FindBin::Bin/../../scripts/zmfilter.pl.in";
open(my $fh, '<', $file) or die "Can't read $file: $!";
my $src = do { local $/; <$fh> };
close($fh);

my $failures = 0;
sub check { my ($n, $ok) = @_; print(($ok ? 'ok' : 'FAIL')." - $n\n"); $failures++ if !$ok; }

my ($body) = $src =~ /^sub checkFilter \{\n(.*?)^\} # end sub checkFilter/ms;
check('found checkFilter', defined $body);
$body //= '';
my $loop = index($body, 'foreach my $event');
my $first = index($body, 'sendSummaryEmail(');
check('a summary send exists', $first >= 0);
check('first summary send is before the event loop', ($first >= 0 && $loop >= 0 && $first < $loop) ? 1 : 0);
check('the send before the loop is for AutoDelete filters', $body =~ /sendSummaryEmail\([^\n]*\)[^\n]*AutoDelete[^\n]*\n\s*\n\s*foreach my \$event/);
print "\n", ($failures ? "$failures failed\n" : "all passed\n");
exit($failures ? 1 : 0);
