#!/usr/bin/perl
#
# zmaudit --interactive asks before each delete. End of input is not a yes,
# an answer of 0 is not an empty answer and Q quits like q.
#
# Run as: perl tests/perl/test_zmaudit_confirm_answers.pl
#
use strict;
use warnings;
use FindBin;
use File::Temp qw(tempdir);

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

my $source = do {
  open(my $fh, '<', "$FindBin::Bin/../../scripts/zmaudit.pl.in") or die "Can't open zmaudit.pl.in: $!";
  local $/;
  <$fh>;
};
my ($confirm) = $source =~ /^(sub confirm \{\n.*?^\}\n)/ms;
die 'Could not extract confirm() from zmaudit.pl.in' unless $confirm;

# Runs confirm() in a child with $input on stdin; prints its answer, or term if it quit.
sub answer {
  my ($input) = @_;
  my $code = 'our ($report, $interactive, $continuous) = (0, 1, 0);'
    .'sub Info {} sub Term { print "term"; exit(0); }'
    .$confirm.'print confirm() ? "yes" : "no";';
  my $file = tempdir(CLEANUP => 1).'/confirm.pl';
  open(my $fh, '>', $file) or die "Can't write $file: $!";
  print $fh $code;
  close($fh);
  open(my $in, '|-', "$^X $file > $file.out 2>/dev/null") or die "Can't run $^X: $!";
  print $in $input;
  close($in);
  open(my $out, '<', "$file.out") or die "Can't read $file.out: $!";
  my $got = do { local $/; <$out> } // '';
  close($out);
  $got =~ s/^, delete Y\/n\/q: //;
  return $got;
}

check('empty answer is yes', answer("\n"), 'yes');
check('y is yes', answer("y\n"), 'yes');
check('n is no', answer("n\n"), 'no');
check('0 is no', answer("0\n"), 'no');
check('q quits', answer("q\n"), 'term');
check('Q quits', answer("Q\n"), 'term');
check('end of input quits instead of answering yes', answer(''), 'term');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
