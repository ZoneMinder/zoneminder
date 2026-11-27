#!/usr/bin/perl
# zmcamtool --topreset only replaced a user or password in a url when another
# ? or & followed it, so "?user=a&pwd=b" kept the password. Runs the regex
# block from toPreset() without a database.
use strict;
use warnings;
use FindBin;

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  if ($got eq $want) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n  got:  $got\n  want: $want\n";
  }
}

my $tool = do {
  open(my $fh, '<', ($ENV{ZM_TEST_ROOT} || "$FindBin::Bin/../..")."/scripts/zmcamtool.pl.in") or die "Can't open zmcamtool.pl.in: $!";
  local $/;
  <$fh>;
};

my ($block) = $tool =~ /if \(!\$noregex\) \{\s*foreach \(\@data\) \{(.*?)\n    \}\n  \}/s
  or die "regex block not found";
my $code = 'sub scrub { my @data = @_; foreach (@data) {'.$block."\n}".' return @data; }';
eval $code;
die $@ if $@;

my %cases = (
  '/video.cgi?user=admin&pwd=secret'          => '/video.cgi?user=<username>&pwd=<pwd>',
  '/video.cgi?pwd=secret&user=admin'          => '/video.cgi?pwd=<pwd>&user=<username>',
  '/video.cgi?user=admin&pwd=secret&ch=1'     => '/video.cgi?user=<username>&pwd=<pwd>&ch=1',
  '/video.cgi?username=admin&password=secret' => '/video.cgi?username=<username>&password=<pwd>',
  '/video.cgi?user=cam-1&pwd=p.ss-w!rd'      => '/video.cgi?user=<username>&pwd=<pwd>',
  '/video.cgi?pwd=p.ss-w!rd&ch=2'            => '/video.cgi?pwd=<pwd>&ch=2',
);
foreach my $in (sort keys %cases) {
  my ($out) = scrub($in);
  check("scrub $in", $out, $cases{$in});
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
