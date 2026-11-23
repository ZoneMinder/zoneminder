#!/usr/bin/perl
use strict;
use warnings;
use IO::Handle;
use Socket;

my $root = $ENV{ZM_TEST_ROOT} // '.';
my $path = "$root/scripts/zmdc.pl.in";

open(my $sfh, '<', $path) or do {
  print "not ok - cannot open $path: $!\n";
  exit 1;
};
my $src = do { local $/; <$sfh> };
close($sfh);

my $failed = 0;

sub ok {
  my ($cond, $name) = @_;
  if ($cond) {
    print "ok - $name\n";
  } else {
    print "not ok - $name\n";
    $failed = 1;
  }
}

my $have_sub = 0;
if ( $src =~ /^(sub readClientCommand \{.*?^\}\n)/ms ) {
  my $code = $1;
  eval $code;
  if ($@) {
    print "not ok - failed to eval readClientCommand: $@\n";
    exit 1;
  }
  $have_sub = 1;
}
if ( !$have_sub ) {
  eval q{
    sub readClientCommand {
      my ($fh, $timeout) = @_;
      my $message = <$fh>;
      return $message;
    }
  };
  if ($@) {
    print "not ok - failed to define fallback readClientCommand: $@\n";
    exit 1;
  }
}

# (1) A full command line is returned intact.
{
  socketpair(A, B, AF_UNIX, SOCK_STREAM, PF_UNSPEC) or die "socketpair: $!";
  A->autoflush(1);
  print A "start;zmc;-m 1\n";
  my $result = readClientCommand(\*B, 2);
  ok(defined($result) && $result eq "start;zmc;-m 1\n", 'reads a full command line');
  close(A);
  close(B);
}

# (2) A client that sends nothing and keeps the socket open must not block.
{
  socketpair(A, B, AF_UNIX, SOCK_STREAM, PF_UNSPEC) or die "socketpair: $!";
  my $start = time();
  my $result;
  my $completed = 0;
  eval {
    local $SIG{ALRM} = sub {
      print "not ok - read blocked\n";
      exit 1;
    };
    alarm 6;
    $result = readClientCommand(\*B, 1);
    alarm 0;
    $completed = 1;
  };
  alarm 0;
  my $elapsed = time() - $start;
  ok($completed, 'silent client does not block the read');
  ok(defined($result) && $result eq '', 'silent client returns an empty string');
  ok($elapsed < 3, "silent client returns quickly (${elapsed}s)");
  close(A);
  close(B);
}

# (3) Partial data followed by EOF is returned as-is.
{
  socketpair(A, B, AF_UNIX, SOCK_STREAM, PF_UNSPEC) or die "socketpair: $!";
  A->autoflush(1);
  print A "partial";
  close(A);
  my $result = readClientCommand(\*B, 2);
  ok(defined($result) && $result eq "partial", 'returns partial data before EOF');
  close(B);
}

exit($failed ? 1 : 0);
